<?php

namespace App\Http\Controllers;

# General
use App\Notification;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

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
            $item['likes'] = $item->likes;
            $item['comments'] = $item->comments;
            $item_display[$master_categories[$item->category->master_category_id]][] = $item;
        }

        $user = $this->user;
        $user['notifications'] = $this->user->notifications;

        $data = [
            'success' => true,
            'item_display' => $item_display,
            'error' => [],
            'user' => $user
        ];

        return response()->json($data);
    }
    
    public function items()
    {
        $items = $this->user->items;

        foreach ($items as $item) {
            $item['likes'] = $item->likes;
            $item['comments'] = $item->comments;
        }

        $user = $this->user;
        $user['notifications'] = $this->user->notifications;

        $data = [
            'success' => true,
            'items' => $items,
            'error' => [],
            'user' => $user
        ];
        
        return response()->json($data);
    }

    public function viewProfile()
    {
        $user = $this->user;
        $user['notifications'] = $this->user->notifications;

        if ($this->request->user_id && ($this->request->user_id != $this->user->id)) {
            $other_user = User::find($this->request->user_id);
            $other_user['items'] = $other_user->items;

            $data = [
                'success' => true,
                'user_data' => $other_user,
                'error' => [],
                'user' => $user
            ];

            Notification::createNotif([
                'message'  => "{$this->user->fullname} visited your profile.",
                'user_ids' => $other_user->id . ",",
                'trigger'  => config('constant.NOTIFICATION_TRIGGER.user'),
                'user_id'  => $this->user->id
            ]);

            return response()->json($data);
        }

        $data = [
            'success' => true,
            'error' => [],
            'user' => $user
        ];

        return response()->json($data);
    }

    public function updateProfile()
    {
        if ($this->request->gender) $this->user->gender = $this->request->gender;
        if ($this->request->location) $this->user->location = $this->request->location;
        if ($this->request->contact) $this->user->contact = $this->request->contact;
        if ($this->request->avatar) $this->user->avatar = $this->request->avatar;
        if ($this->request->birth_date) $this->user->birth_date = $this->request->birth_date;

        $this->user->save();

        $user = $this->user;
        $user['notifications'] = $this->user->notifications;

        $data = [
            'success' => true,
            'error' => [],
            'user' => $user
        ];

        return response()->json($data);
    }
}
