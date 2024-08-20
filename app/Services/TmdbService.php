<?php

namespace App\Services;

use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;

class TmdbService
{
    protected static $apiKey;

    public function __construct()
    {
        self::$apiKey = config('services.tmdb.token');
    }

    public static function initialize()
    {
        self::$apiKey = config('services.tmdb.token');
        if (!self::$apiKey) {
            throw new Exception('TMDB API key is missing.');
        }
    }

    public static function getPopularMovies($page = 1)
    {
        try {
            self::initialize();
            $response = Http::withToken(self::$apiKey)
                ->get('https://api.themoviedb.org/3/movie/popular', [
                    'page' => $page
                ]);

            return $response->json()['results'];
        } catch (Exception $e) {
            throw new Exception('Failed to fetch popular movies');
        }
    }

    public static function getNowPlayingMovies($page = 1)
    {
        try {
            self::initialize();
            $response = Http::withToken(self::$apiKey)
                ->get('https://api.themoviedb.org/3/movie/now_playing', [
                    'page' => $page
                ]);

            return $response->json()['results'];
        } catch (Exception $e) {
            throw new Exception('Failed to fetch now playing movies');
        }
    }

    public static function getMovieById($id)
    {
        try {
            self::initialize();
            $response = Http::withToken(self::$apiKey)
                ->get("https://api.themoviedb.org/3/movie/{$id}");

            return $response->json();
        } catch (Exception $e) {
            throw new Exception('Failed to fetch movie');
        }
    }

    public static function getPopularTvShows($page = 1)
    {
        try {
            self::initialize();
            $response = Http::withToken(self::$apiKey)
                ->get('https://api.themoviedb.org/3/tv/popular', [
                    'page' => $page
                ]);

            return $response->json()['results'];
        } catch (Exception $e) {
            throw new Exception('Failed to fetch popular TV shows');
        }
    }

    public static function getTopRatedTvShows($page = 1)
    {
        try {
            self::initialize();
            $response = Http::withToken(self::$apiKey)
                ->get('https://api.themoviedb.org/3/tv/top_rated', [
                    'page' => $page
                ]);

            return $response->json()['results'];
        } catch (Exception $e) {
            throw new Exception('Failed to fetch top-rated TV shows');
        }
    }

    public static function getTvShowById($id)
    {
        try {
            self::initialize();
            $response = Http::withToken(self::$apiKey)
                ->get("https://api.themoviedb.org/3/tv/{$id}?append_to_response=credits,videos,images");

            $tvshow = $response->json();

            // Ensure the response has the expected structure
            if (!is_array($tvshow) || !isset($tvshow['id'])) {
                throw new Exception('Invalid TV show data received from TMDB API');
            }

            return $tvshow;
        } catch (Exception $e) {
            throw new Exception('Failed to fetch TV show: ' . $e->getMessage());
        }
    }


    public static function getMovieGenres()
    {
        try {
            self::initialize();
            $response = Http::withToken(self::$apiKey)
                ->get('https://api.themoviedb.org/3/genre/movie/list', [
                    'language' => 'en-US'
                ]);

            return $response->json()['genres'];
        } catch (Exception $e) {
            throw new Exception('Failed to fetch movie genres');
        }
    }

    public static function getTvGenres()
    {
        try {
            self::initialize();
            $response = Http::withToken(self::$apiKey)
                ->get('https://api.themoviedb.org/3/genre/tv/list', [
                    'language' => 'en-US'
                ]);

            return $response->json()['genres'];
        } catch (Exception $e) {
            throw new Exception('Failed to fetch TV genres');
        }
    }

    public static function search($query, $type, $page = 1)
    {

        try {
            self::initialize();
            $endpoint = match ($type) {
                'movie' => 'https://api.themoviedb.org/3/search/movie',
                'tv' => 'https://api.themoviedb.org/3/search/tv',
                'multi' => 'https://api.themoviedb.org/3/search/multi',
                default => throw new Exception('Invalid search type'),
            };

            $response = Http::withToken(self::$apiKey)
                ->get($endpoint, [
                    'query' => $query,
                    'include_adult' => false,
                    'language' => 'en-US',
                    'page' => $page,
                ]);

            return $response->json();
        } catch (Exception $e) {
            throw new Exception('Failed to perform search');
        }
    }

    public static function getTrailers($type, $id)
    {

        try {
            self::initialize();
            $endpoint = $type === 'movie'
                ? "https://api.themoviedb.org/3/movie/{$id}/videos"
                : "https://api.themoviedb.org/3/tv/{$id}/videos";

            $response = Http::withToken(self::$apiKey)
                ->get($endpoint, [
                    'language' => 'en-US'
                ])->json();

            return collect($response['results'])->filter(function ($video) {
                return $video['type'] === 'Trailer';
            });
        } catch (Exception $e) {
            throw new Exception('Failed to fetch trailers');
        }
    }

    public static function getTopRatedMoviesByGenre($genreId, $page = 1)
    {
        try {
            self::initialize();
            $topRatedMovies = Http::withToken(self::$apiKey)
                ->get('https://api.themoviedb.org/3/discover/movie', [
                    'with_genres' => $genreId,
                    'sort_by' => 'vote_average.desc'
                ])
                ->json()['results'];

            return $topRatedMovies;
        } catch (Exception $e) {
            Log::error('Failed to fetch top-rated movies by genre', ['error' => $e->getMessage()]);
            throw new Exception('Failed to fetch top-rated movies by genre');
        }
    }


    public static function getTopRatedTvShowsByGenre($genreId, $page = 1)
    {

        try {
            $response = Http::withToken(self::$apiKey)
                ->get('https://api.themoviedb.org/3/discover/tv', [
                    'with_genres' => $genreId,
                    'sort_by' => 'vote_average.desc',
                    'page' => $page
                ]);

            return $response->json()['results'] ?? [];
        } catch (Exception $e) {
            throw new Exception('Failed to fetch top-rated TV shows by genre');
        }
    }

}
