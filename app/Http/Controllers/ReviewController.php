<?php

namespace App\Http\Controllers;

use App\Exceptions\BadRequestException;
use App\Exceptions\EntityNotFoundException;
use App\Models\Movie;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    private Review $review;
    private Movie $movie;
    private User $user;

    public function __construct(Review $review, Movie $movie, User $user)
    {
        $this->review = $review;
        $this->movie = $movie;
        $this->user = $user;
        $this->middleware('jwt.auth')->only(['store']);
        $this->middleware('role:member|worker|admin')->only(['store']);
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $movieId)
    {
        $request->validate($this->review->rules(), $this->review->feedback());
        $movie = $this->movie->with('reviews')->find($movieId);
        if($movie === null) {
            throw new EntityNotFoundException('Movie not found');
        }
        $user = auth()->user();
        $userReview = $movie->reviews->first(function(Review $review) use($user) {
            return $review->user_id === $user->id;
        });
        if($userReview !== null) {
            throw new BadRequestException('You can only set one review per movie');
        }
        $review = new Review();
        $review->fill($request->all());
        $review->user_id = $user->id;
        $movie->reviews()->save($review);
        $dto = [
            'id' => $review->id,
            'comment' => $review->message,
            'rating' => $review->rating
        ];
        return response($dto, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Review $review)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Review $review)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Review $review)
    {
        //
    }
}
