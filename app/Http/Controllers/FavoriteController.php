<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    /**
     * Fetch popular and top-rated TV shows with genres
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $currentUser = Auth::user();

            $favoriteMovies = $currentUser->favorites()->where('media_type', 'movie')
                ->get()
                ->map(function ($favMovie) {
                    $movie = Http::withToken(config('services.tmdb.token'))
                        ->get("https://api.themoviedb.org/3/movie/{$favMovie->tmdb_id}")
                        ->json();
                    $movie['type'] = 'movie';
                    return $movie;
                });

            $favoriteTvShows = $currentUser->favorites()->where('media_type', 'tv')
                ->get()
                ->map(function ($favTvShow) {
                    $tvShow = Http::withToken(config('services.tmdb.token'))
                        ->get("https://api.themoviedb.org/3/tv/{$favTvShow->tmdb_id}")
                        ->json();
                    $tvShow['type'] = 'tv_show';
                    return $tvShow;
                });


            return ApiResponse::success(
                message: 'Favorites fetched successfully',
                data: [
                    'movies' => $favoriteMovies,
                    'tv_shows' => $favoriteTvShows
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch favorites',
                code: 'favorites:fetch_failed',
                statusCode: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Search for movies or TV shows by query string
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'query' => 'required|string',
            'type' => 'required|string|in:movie,tv,multi',
        ], [
            'query.required' => 'Query string is required',
            'query.string' => 'Query string must be a string',
            'type.required' => 'Type is required',
            'type.string' => 'Type must be a string',
            'type.in' => 'Type must be either "movie", "tv", or "multi"',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(
                message: 'Validation failed',
                code: 'search:validation_failed',
                statusCode: 422,
                errors: $validator->errors()
            );
        }

        try {
            $query = $request->input('query');
            $type = $request->input('type');

            // Determine the correct TMDB API endpoint based on search type
            $endpoint = '';
            switch ($type) {
                case 'movie':
                    $endpoint = 'https://api.themoviedb.org/3/search/movie';
                    break;
                case 'tv':
                    $endpoint = 'https://api.themoviedb.org/3/search/tv';
                    break;
                case 'multi':
                    $endpoint = 'https://api.themoviedb.org/3/search/multi';
                    break;
            }

            // Perform the search request to TMDB API
            $response = Http::withToken(config('services.tmdb.token'))
                ->get($endpoint, [
                    'query' => $query,
                    'include_adult' => false,
                    'language' => 'en-US',
                    'page' => $request->input('page', 1),
                ])
                ->json();

            return ApiResponse::success(
                message: 'Search results fetched successfully',
                data: $response['results']
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch search results',
                code: 'search:fetch_failed',
                statusCode: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Get all trailer links for a movie or TV show.
     *
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function getAllTrailerLinks($type, $id): JsonResponse
    {
        try {
            // Determine the correct endpoint based on the type (movie or tv)
            $endpoint = $type === 'movie'
                ? "https://api.themoviedb.org/3/movie/{$id}/videos"
                : "https://api.themoviedb.org/3/tv/{$id}/videos";

            // Make the API request
            $response = Http::withToken(config('services.tmdb.token'))
                ->get($endpoint, [
                    'language' => 'en-US'
                ])->json();

            // Filter the results to include all trailers (both official and non-official)
            $trailers = collect($response['results'])->filter(function ($video) {
                return $video['type'] === 'Trailer';
            });

            // If no trailers are found, return a 404 response
            if ($trailers->isEmpty()) {
                return ApiResponse::error(
                    message: 'No trailers found for this movie or TV show',
                    code: 'trailers:not_found',
                    statusCode: 404,
                );
            }

            // Construct the video links array
            $videoLinks = $trailers->map(function ($trailer) {
                return [
                    'name' => $trailer['name'],
                    'link' => "https://www.youtube.com/watch?v=" . $trailer['key'],
                    'official' => $trailer['official'],
                    'published_at' => $trailer['published_at']
                ];
            });

            // Return the success response with the list of video links
            return ApiResponse::success(
                message: 'Trailer links fetched successfully',
                data: $videoLinks
            );

        } catch (Exception $e) {
            // Handle any errors that occur during the request
            return ApiResponse::error(
                message: 'Failed to fetch trailer links',
                code: 'trailers:fetch_failed',
                statusCode: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Get the top 5 movies and TV shows in a specified genre.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTop5InGenre(Request $request): JsonResponse
    {
        try {
            // Validate the incoming request
            $validator = Validator::make($request->all(), [
                'genre_id' => 'required|integer',
            ], [
                'genre_id.required' => 'Genre ID is required',
                'genre_id.integer' => 'Genre ID must be an integer',
            ]);

            if ($validator->fails()) {
                return ApiResponse::error(
                    message: 'Validation failed',
                    code: 'top5:validation_failed',
                    statusCode: 422,
                    errors: $validator->errors()
                );
            }
            // Get the genre ID from the request
            $genreId = $request->input('genre_id');

            // Fetch the top rated movies in the specified genre
            $topRatedMovies = Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/discover/movie', [
                    'with_genres' => $genreId,
                    'sort_by' => 'vote_average.desc'
                ])
                ->json()['results'];

            // Fetch the top rated TV shows in the specified genre
            $topRatedTvShows = Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/discover/tv', [
                    'with_genres' => $genreId,
                    'sort_by' => 'vote_average.desc'
                ])
                ->json()['results'];

            // Return only the top 5 movies and top 5 TV shows in the specified genre
            $top5Movies = array_slice($topRatedMovies, 0, 5);
            $top5TvShows = array_slice($topRatedTvShows, 0, 5);

            return ApiResponse::success(
                message: 'Top 5 movies and TV shows fetched successfully',
                data: [
                    'movies' => $top5Movies,
                    'tv_shows' => $top5TvShows
                ]
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch top 5 movies and TV shows',
                code: 'top5:fetch_failed',
                statusCode: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'media_id' => 'required|integer',
            'media_type' => 'required|string|in:movie,tv',
        ], [
            'media_id.required' => 'Media ID is required',
            'media_id.integer' => 'Media ID must be an integer',
            'media_type.required' => 'Media type is required',
            'media_type.string' => 'Media type must be a string',
            'media_type.in' => 'Media type must be either "movie" or "tv"',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(
                message: 'Validation failed',
                code: 'favorites:validation_failed',
                statusCode: 422,
                errors: $validator->errors()
            );
        }

        try {
            $currentUser = Auth::user();

            $favorite = new Favorite();
            $favorite->tmdb_id = $request->get('media_id');
            $favorite->media_type = $request->get('media_type'); // 'movie' or 'tv'
            $favorite->user_id = $currentUser->id;
            $favorite->save();

            return ApiResponse::success(
                message: 'Favorite added successfully',
                data: new FavoriteResource($favorite)
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to add favorite',
                code: 'favorites:add_failed',
                statusCode: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'media_id' => 'required|integer',
            'media_type' => 'required|string|in:movie,tv',
        ], [
            'media_id.required' => 'Media ID is required',
            'media_id.integer' => 'Media ID must be an integer',
            'media_type.required' => 'Media type is required',
            'media_type.string' => 'Media type must be a string',
            'media_type.in' => 'Media type must be either "movie" or "tv"',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(
                message: 'Validation failed',
                code: 'favorites:validation_failed',
                statusCode: 422,
                errors: $validator->errors()
            );
        }

        try {
            $currentUser = Auth::user();

            $favorite = Favorite::where('tmdb_id', $request->get('media_id'))
                ->where('media_type', $request->get('media_type'))
                ->where('user_id', $currentUser->id)
                ->first();

            if ($favorite) {
                $favorite->delete();

                return ApiResponse::success(
                    message: 'Favorite removed successfully'
                );
            } else {
                return ApiResponse::error(
                    message: 'Favorite not found',
                    code: 404,
                    statusCode: 'favorites:not_found'
                );
            }
        } catch (Exception $e) {
            return ApiResponse::error(
                message: 'Failed to remove favorite',
                code: 'favorites:remove_failed',
                statusCode: 500,
                errors: $e->getMessage()
            );
        }
    }

}
