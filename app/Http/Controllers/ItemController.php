<?php

namespace App\Http\Controllers;

# General
use App\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

# Models
use App\User;
use App\Item;
use App\Brand;
use App\Category;

class ItemController extends Controller
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

    public function view()
    {
        $item = Item::find($this->request->item_id);

        if (!$item) return response()->json([
            'success' => false,
            'error' => ['Item does not exist.'],
            'user' => $this->user
        ]);
        
        $item['likes'] = $item->likes;
        $item['comments'] = $item->comments;

        $user = $this->user;
        $user['notifications'] = $this->user->notifications;

        $data = [
            'success' => true,
            'item' => $item,
            'error' => [],
            'user' => $user
        ];

        return response()->json($data);
    }
    
    public function viewAdd()
    {
        $user = $this->user;
        $user['notifications'] = $this->user->notifications;
        
        $data = [
            'success' => true,
            'conditions' => config('constant.ITEM_CONDITIONS'),
            'categories' => config('constant.ITEM_CATEGORIES'),
            'brands' => Brand::all()->pluck('name', 'id'),
            'error' => [],
            'user' => $user
        ];

        return response()->json($data);
    }

    public function addSave()
    {
        $error = [];

        if (!$this->request->category_id) $error[] = 'Category is required.';
        if (!$this->request->name) $error[] = 'Name is required.';
        if (!$this->request->description) $error[] = 'Description is required.';
        if (!$this->request->price) $error[] = 'Price is required.';
        if (!$this->request->condition) $error[] = 'Condition is required.';

        if ($error) return response()->json([
            'success' => false,
            'error' => $error
        ]);

        $request = [
            'user_id' => $this->user->id,
            'category_id' => $this->request->category_id,
            'brand_id' => isset($this->request->brand_id) ? $this->request->brand_id : null,
            'name' => $this->request->name,
            'description' => $this->request->description,
            'price' => $this->request->price,
            'condition' => $this->request->condition,
            'size' => isset($this->request->size) ? $this->request->size : null,
            'shipping_fee' => isset($this->request->shipping_fee) ? $this->request->shipping_fee : 0,
            'ships_from' => isset($this->request->ships_from) ? $this->request->ships_from : '',
            'shipping_duration' => isset($this->request->shipping_duration) ? $this->request->shipping_duration : null
        ];

        $item = Item::create($request);

        $user = $this->user;
        $user['notifications'] = $this->user->notifications;

        $data = [
            'success' => true,
            'item' => $item,
            'error' => [],
            'user' => $user
        ];

        return response()->json($data);
    }

    public function like()
    {
        $item = Item::find($this->request->item_id);

        if (!$item) return response()->json([
            'success' => false,
            'error' => ['Item does not exist.'],
            'user' => $this->user
        ]);

        if ($item->user_id == $this->user->id) {
            return response()->json([
                'success' => false,
                'error' => ['Cannot like your own item.'],
                'user' => $this->user
            ]);
        }

        if ($this->user->likes()->where('item_id', $item->id)->first()) {
            return response()->json([
                'success' => false,
                'error' => ['Already liked this item.'],
                'user' => $this->user
            ]);
        }

        $this->user->likes()->create(['item_id' => $item->id]);

        Notification::createNotif([
            'message'  => "{$this->user->fullname} liked your item '{$item->name}'.",
            'user_ids' => $item->user->id . ",",
            'link'     => route('item_view', ['item_id' => $item->id]),
            'trigger'  => config('constant.NOTIFICATION_TRIGGER.user'),
            'user_id'  => $this->user->id
        ]);
        
        $item['likes'] = $item->likes;
        $item['comments'] = $item->comments;

        $user = $this->user;
        $user['notifications'] = $this->user->notifications;

        $data = [
            'success' => true,
            'item' => $item,
            'error' => [],
            'user' => $user
        ];

        return response()->json($data);
    }

    public function comment()
    {
        $item = Item::find($this->request->item_id);

        if (!$item) return response()->json([
            'success' => false,
            'error' => ['Item does not exist.'],
            'user' => $this->user
        ]);

        $request = [
            'item_id' => $item->id,
            'body' => $this->request->body
        ];

        $this->user->comments()->create($request);
        
        if ($item->user->id != $this->user->id) {
            Notification::createNotif([
                'message'  => "{$this->user->fullname} commented on your item '{$item->name}', '{$this->request->body}'.",
                'user_ids' => $item->user->id . ",",
                'link'     => route('item_view', ['item_id' => $item->id]),
                'trigger'  => config('constant.NOTIFICATION_TRIGGER.user'),
                'user_id'  => $this->user->id
            ]);
        }
        
        $item['likes'] = $item->likes;
        $item['comments'] = $item->comments;

        $user = $this->user;
        $user['notifications'] = $this->user->notifications;

        $data = [
            'success' => true,
            'item' => $item,
            'error' => [],
            'user' => $user
        ];

        return response()->json($data);
    }

    public function delete()
    {
        $item = Item::find($this->request->item_id);

        if (!$item) return response()->json([
            'success' => false,
            'error' => ['Item does not exist.'],
            'user' => $this->user
        ]);

        if ($item->user_id != $this->user->id) {
            return response()->json([
                'success' => false,
                'error' => ['Cannot delete this item because it does not belong to the user.'],
                'user' => $this->user
            ]);
        }

        $item->delete();

        return redirect()->route('user_items');
    }

    public function search()
    {
        $keyword = $this->request->keyword;

        $items = Item::where('name', 'like', "%$keyword%")->orWhere('description', 'like', "%$keyword%")->get();

        foreach ($items as $item) {
            $item['likes'] = $item->likes;
            $item['comments'] = $item->comments;
        }

        $error = [];

        $user = $this->user;
        $user['notifications'] = $this->user->notifications;
        
        $data = [
            'success' => true,
            'keyword' => $keyword,
            'item_count' => $items->count(),
            'items' => $items,
            'error' => $error,
            'user' => $user
        ];
        
        return response()->json($data);
    }

    public function viewByCategory()
    {
        $category = Category::find($this->request->category_id);

        if (!$category) return response()->json([
            'success' => false,
            'error' => ['Category does not exist.'],
            'user' => $this->user
        ]);

        $items = Item::where('category_id', $category->id)->get();

        foreach ($items as $item) {
            $item['likes'] = $item->likes;
            $item['comments'] = $item->comments;
        }

        $error = [];

        $user = $this->user;
        $user['notifications'] = $this->user->notifications;

        $data = [
            'success' => true,
            'category' => $category->name,
            'item_count' => $items->count(),
            'items' => $items,
            'error' => $error,
            'user' => $user
        ];

        return response()->json($data);
    }
    
    public function buy()
    {
        $request = $this->request->only(['item_id', 'shipping_address']);

        $validator = Validator::make($request, [
            'item_id' => 'required',
            'shipping_address' => 'required',
        ]);

        if ($validator->fails()) {
            $data = [
                'success' => false,
                'error' => $validator->errors()->all(),
                'user' => $this->user
            ];

            return response()->json($data);
        }

        $item = Item::find($this->request->item_id);

        if (!$item) {
            $data = [
                'success' => false,
                'error' => ['Item does not exist.'],
                'user' => $this->user
            ];

            return response()->json($data);
        }

        if (!$item->isAvailable()) {
            $data = [
                'success' => false,
                'error' => ['Item is not available.'],
                'user' => $this->user
            ];

            return response()->json($data);
        }

        $purchase = [
            'item_id' => $item->id,
            'shipping_address' => $this->request->shipping_address,
            'status' => config('constant.PURCHASE_STATUS.pending')
        ];

        $this->user->purchases()->create($purchase);

        $payment = [
            'item_id' => $item->id,
            'amount' => $item->price
        ];

        $this->user->payments()->create($payment);

        $item->update(['status' => config('constant.ITEM_STATUS.pending')]);

        $item['likes'] = $item->likes;
        $item['comments'] = $item->comments;

        $data = [
            'success' => true,
            'item' => $item,
            'error' => [],
            'user' => $this->user
        ];

        return response()->json($data);
    }
}
