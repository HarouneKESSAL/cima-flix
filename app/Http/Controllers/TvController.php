<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TvController extends Controller
{
    /**
     * Fetch popular and top-rated TV shows with genres
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

            // Fetch popular TV shows with pagination
            $popularTvResponse = Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/tv/popular', [
                    'page' => $page
                ]);

            $popularTv = $popularTvResponse->json()['results'];

            // Fetch top-rated TV shows with pagination
            $topRatedTvResponse = Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/tv/top_rated', [
                    'page' => $page
                ]);

            $topRatedTv = $topRatedTvResponse->json()['results'];

            // Fetch TV genres (not paginated)
            $genres = Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/genre/tv/list')
                ->json()['genres'];

            // Use array_slice to limit the number of results returned
            $popularTv = array_slice($popularTv, 0, $size);
            $topRatedTv = array_slice($topRatedTv, 0, $size);


            return ApiResponse::success(
                message: 'TV shows fetched successfully',
                data: [
                    'popular' => $popularTv,
                    'topRated' => $topRatedTv,
                    'genres' => $genres
                ],
                page: $page,
                size: $size,
                total: count($popularTv) + count($topRatedTv)
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch TV shows',
                code: 'tvshows:fetch_failed',
                statusCode: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Fetch a single TV show by ID
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            //validate the id
            $validator = validator(['id' => $id],
                ['id' => 'required|integer'],
                ['id' => 'The TV show ID is required and must be an integer']);

            if ($validator->fails()) {
                return ApiResponse::error(
                    message: 'Validation failed',
                    code: 'validation:failed',
                    statusCode: 422,
                    errors: $validator->errors()
                );
            }

            // Fetch TV show details
            $tvshow = Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/tv/' . $id . '?append_to_response=credits,videos,images')
                ->json();

            // Extracting necessary information
            $necessaryDetails = [
                'id' => $tvshow['id'],
                'name' => $tvshow['name'],
                'original_name' => $tvshow['original_name'],
                'overview' => $tvshow['overview'],
                'poster_path' => $tvshow['poster_path'],
                'backdrop_path' => $tvshow['backdrop_path'],
                'vote_average' => $tvshow['vote_average'],
                'vote_count' => $tvshow['vote_count'],
                'genres' => $tvshow['genres'],
                'first_air_date' => $tvshow['first_air_date'],
                'last_air_date' => $tvshow['last_air_date'],
                'number_of_seasons' => $tvshow['number_of_seasons'],
                'number_of_episodes' => $tvshow['number_of_episodes'],
                'in_production' => $tvshow['in_production'],
                'status' => $tvshow['status'],
            ];

            return ApiResponse::success(
                message: 'TV show fetched successfully',
                data: $necessaryDetails
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch TV show',
                code: 'tvshow:fetch_failed',
                statusCode: 500,
                errors: $e->getMessage()
            );
        }
    }

}
