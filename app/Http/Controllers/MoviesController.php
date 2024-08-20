<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MoviesController extends Controller
{
    /**
     * Fetch popular and now playing movies with genres
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get page and size from request, with defaults of page=1 and size=10
            $page = $request->input('page', 1);
            $size = $request->input('size', 10);

            // Fetch popular movies with pagination
            $popularMoviesResponse = Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/movie/popular', [
                    'page' => $page
                ]);

            $popularMovies = $popularMoviesResponse->json()['results'];

            // Fetch now playing movies with pagination
            $nowPlayingMoviesResponse = Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/movie/now_playing', [
                    'page' => $page
                ]);

            $nowPlayingMovies = $nowPlayingMoviesResponse->json()['results'];

            // Fetch genres (not paginated, just limited to the first 3 as you did)
            $genres = Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/genre/movie/list', [
                    'language' => 'en-US',
                    'limit' => 3
                ])
                ->json()['genres'];

            // Return a successful API response with the paginated data
            return ApiResponse::success(
                message: 'Movies fetched successfully',
                data: [
                    'popular' => array_slice($popularMovies, 0, $size),
                    'nowPlaying' => array_slice($nowPlayingMovies, 0, $size),
                    'genres' => $genres
                ],
                page: $page,
                size: $size,
                total: count($popularMovies) + count($nowPlayingMovies)
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch movies',
                code:'movies:fetch_failed',
                statusCode: 500,errors: $e->getMessage()
            );
        }
    }


    /**
     * Fetch a single movie by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            //validate the id
            $validator = validator(['id' => $id], ['id' => 'required|integer'],[
                'id.required' => 'The movie ID is required',
                'id.integer' => 'The movie ID must be an integer'
            ]);

            if ($validator->fails()) {
                return ApiResponse::error(
                    message: 'Validation error',
                    code: 'movie:validation_error',
                    statusCode: 400,
                    errors: $validator->errors()
                );
            }
            // Fetch the movie by ID
            $movie = Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/movie/'.$id.'?append_to_response=credits,videos,images')
                ->json();

            // Filter the necessary fields
            $filteredMovie = [
                'id' => $movie['id'],
                'title' => $movie['title'],
                'overview' => $movie['overview'],
                'release_date' => $movie['release_date'],
                'runtime' => $movie['runtime'],
                'poster_path' => $movie['poster_path'],
                'backdrop_path' => $movie['backdrop_path'],
                'vote_average' => $movie['vote_average'],
                'genres' => $movie['genres'],
                ];

            return ApiResponse::success(
                message: 'Movie fetched successfully',
                data: $filteredMovie
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch movie',
                code:'movie:fetch_failed',
                statusCode: 500,
                errors: $e->getMessage()
            );
        }
    }

}
