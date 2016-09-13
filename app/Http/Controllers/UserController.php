<?php

namespace App\Http\Controllers;

# General
use App\Notification;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Input;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Intervention\Image\Facades\Image;

# Models
use App\Item;
use App\User;

class UserController extends Controller
{
    public function __construct(Request $request)
    {
        try {
            $this->user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
        } catch (TokenInvalidException $e) {
        } catch (JWTException $e) {
        }
        
        $this->request = $request;
    }
    
    public function home()
    {
        $items = Item::all()->sortByDesc('created_at');

        $master_categories = config('constant.ITEM_CATEGORIES');
        $item_display = [];
        foreach ($master_categories as $master_category) {
            $item_display[$master_category] = [];
        }

        foreach ($items as $item) {
            $item_display[$master_categories[$item->category->master_category_id]][] = $item->getReturn();
        }

        $data = [
            'success' => true,
            'item_display' => $item_display,
            'error' => [],
            'user' => $this->user->getReturn()
        ];

        return response()->json($data);
    }
    
    public function items()
    {
        $items = [];
        foreach ($this->user->items as $item) {
            $items[] = $item->getReturn();
        }

        $data = [
            'success' => true,
            'items' => $items,
            'error' => [],
            'user' => $this->user->getReturn()
        ];
        
        return response()->json($data);
    }

    public function viewProfile()
    {
        $user = $this->user;
        $user['notifications'] = $this->user->notifications;

        if ($this->request->user_id && ($this->request->user_id != $this->user->id)) {
            $other_user = User::find($this->request->user_id);

            $items = collect();
            foreach ($other_user->items as $item) {
                $items[] = $item->getReturn();
            }

            $data = [
                'success' => true,
                'user_data' => array_merge($other_user->getReturn(false), [
                    'items' => $items,
                    'score' => $user->getRatings(config('constant.PURCHASE_RATING.great'))->count() - $user->getRatings(config('constant.PURCHASE_RATING.poor'))->count(),
                    'listings' => $user->items->count(),
                    'ratings' => [
                        'great' => $user->getRatings(config('constant.PURCHASE_RATING.great'))->count(),
                        'good' => $user->getRatings(config('constant.PURCHASE_RATING.good'))->count(),
                        'poor' => $user->getRatings(config('constant.PURCHASE_RATING.poor'))->count(),
                    ],
                    'has_followed' => $this->user->follows()->where('following_user_id', $other_user->id)->exists()
                ]),
                'error' => [],
                'user' => $user->getReturn()
            ];

            Notification::createNotif([
                'message'  => "{$this->user->fullname} visited your profile.",
                'user_ids' => $other_user->id . ",",
                'trigger'  => config('constant.NOTIFICATION_TRIGGER.user'),
                'user_id'  => $this->user->id
            ]);

            return response()->json($data);
        } else {
            $items = collect();
            foreach ($user->items as $item) {
                $items[] = $item->getReturn();
            }

            $data = [
                'success' => true,
                'error' => [],
                'user' => array_merge($user->getReturn(),[
                    'items' => $items,
                    'score' => $user->getRatings(config('constant.PURCHASE_RATING.great'))->count() - $user->getRatings(config('constant.PURCHASE_RATING.poor'))->count(),
                    'listings' => $user->items->count(),
                    'ratings' => [
                        'great' => $user->getRatings(config('constant.PURCHASE_RATING.great'))->count(),
                        'good' => $user->getRatings(config('constant.PURCHASE_RATING.good'))->count(),
                        'poor' => $user->getRatings(config('constant.PURCHASE_RATING.poor'))->count(),
                    ]
                ])
            ];

            return response()->json($data);
        }
    }

    public function updateProfile()
    {
        if ($this->request->gender) $this->user->gender = $this->request->gender;
        if ($this->request->location) $this->user->location = $this->request->location;
        if ($this->request->contact) $this->user->contact = $this->request->contact;
        if ($this->request->birth_date) $this->user->birth_date = $this->request->birth_date;
        if ($this->request->avatar) {
            $image = Input::file('avatar');
            $filename  = time() . '.' . $image->getClientOriginalExtension();
            $path = public_path('images/users' . $filename);

            Image::make($image->getRealPath())->resize(200, 200)->save($path);

            $this->user->avatar = $filename;
        }

        $this->user->save();

        $data = [
            'success' => true,
            'error' => [],
            'user' => $this->user->getReturn()
        ];

        return response()->json($data);
    }

    public function likesViews()
    {
        $item_likes = [];
        foreach ($this->user->likes->sortByDesc('created_at') as $like) {
            $item_likes[] = $like->item->getReturn();
        }

        $item_views = [];
        foreach ($this->user->item_views->sortByDesc('created_at')->unique('item_id') as $item_view) {
            $item_views[] = $item_view->item->getReturn();
        }

        $data = [
            'success' => true,
            'item_likes' => $item_likes,
            'item_views' => $item_views,
            'error' => [],
            'user' => $this->user->getReturn()
        ];

        return response()->json($data);
    }

    public function follow()
    {
        if (!$this->request->user_id) {
            return response()->json([
                'success' => false,
                'error' => ['User id is required.'],
                'user' => $this->user->getReturn()
            ]);
        }

        $following_user = User::find($this->request->user_id);

        if (!$following_user) {
            return response()->json([
                'success' => false,
                'error' => ['User does not exist.'],
                'user' => $this->user->getReturn()
            ]);
        }

        if ($following_user->id == $this->user->id) {
            return response()->json([
                'success' => false,
                'error' => ['Cannot follow yourself.'],
                'user' => $this->user->getReturn()
            ]);
        }

        if ($this->user->follows()->where('following_user_id', $following_user->id)->first()) {
            return response()->json([
                'success' => false,
                'error' => ['Already followed this user.'],
                'user' => $this->user->getReturn()
            ]);
        }

        $this->user->follows()->create(['following_user_id' => $following_user->id]);

        $data = [
            'success' => true,
            'error' => [],
            'user' => $this->user->getReturn()
        ];

        return response()->json($data);
    }

    public function unfollow()
    {
        if (!$this->request->user_id) {
            return response()->json([
                'success' => false,
                'error' => ['User id is required.'],
                'user' => $this->user->getReturn()
            ]);
        }

        $following_user = User::find($this->request->user_id);

        if (!$following_user) {
            return response()->json([
                'success' => false,
                'error' => ['User does not exist.'],
                'user' => $this->user->getReturn()
            ]);
        }

        if ($following_user->id == $this->user->id) {
            return response()->json([
                'success' => false,
                'error' => ['Cannot unfollow yourself.'],
                'user' => $this->user->getReturn()
            ]);
        }

        if (!$this->user->follows()->where('following_user_id', $following_user->id)->first()) {
            return response()->json([
                'success' => false,
                'error' => ['You are not following this user.'],
                'user' => $this->user->getReturn()
            ]);
        }

        $this->user->follows()
            ->where('following_user_id', $following_user->id)
            ->first()
            ->delete();

        $data = [
            'success' => true,
            'error' => [],
            'user' => $this->user->getReturn()
        ];

        return response()->json($data);
    }

    public function viewRatings()
    {
        if ($this->request->user_id && ($this->user->id != $this->request->user_id)) {
            $other_user = User::find($this->request->user_id);

            $data = [
                'success' => true,
                'user_data' => array_merge($other_user->getReturn(false), [
                    'ratings' => [
                        'great' => $other_user->getRatings(config('constant.PURCHASE_RATING.great')),
                        'good' => $other_user->getRatings(config('constant.PURCHASE_RATING.good')),
                        'poor' => $other_user->getRatings(config('constant.PURCHASE_RATING.poor')),
                    ]
                ]),
                'error' => [],
                'user' => $this->user->getReturn()
            ];

            return response()->json($data);
        } else {
            $ratings = [
                'great' => $this->user->getRatings(config('constant.PURCHASE_RATING.great')),
                'good' => $this->user->getRatings(config('constant.PURCHASE_RATING.good')),
                'poor' => $this->user->getRatings(config('constant.PURCHASE_RATING.poor')),
            ];
        }
        
        $data = [
            'success' => true,
            'ratings' => $ratings,
            'error' => [],
            'user' => $this->user->getReturn()
        ];

        return response()->json($data);
    }
}
