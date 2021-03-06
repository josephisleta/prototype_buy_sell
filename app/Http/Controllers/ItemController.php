<?php

namespace App\Http\Controllers;

# General
use App\Condition;
use App\ItemView;
use App\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;
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
            'error'   => ['Item does not exist.'],
            'user'    => $this->user->getReturn()
        ]);
        
        $this->user->item_views()->create(['item_id' => $item->id]);

        $is_buyer = $this->user->purchases()->where('item_id', $item->id)->exists();

        $data = [
            'success'  => true,
            'is_buyer' => $is_buyer,
            'item'     => $item->getReturn(),
            'error'    => [],
            'user'     => $this->user->getReturn()
        ];

        return response()->json($data);
    }
    
    public function viewAdd()
    {
        $data = [
            'success'    => true,
            'conditions' => Condition::all(),
            'categories' => Category::all(),
            'brands'     => Brand::all(),
            'error'      => [],
            'user'       => $this->user->getReturn()
        ];

        return response()->json($data);
    }

    public function addSave()
    {
        $error = [];

        Log::info($this->request->method());
        Log::info($this->request);

        if (!$this->request->name) $error[] = 'Name is required.';
        if (!$this->request->description) $error[] = 'Description is required.';
        if (!$this->request->price) $error[] = 'Price is required.';
        if (!$this->request->category_id) $error[] = 'Category id is required.';
        if (!$this->request->condition_id) $error[] = 'Condition id is required.';

        if ($this->request->category_id && !Category::where('id', $this->request->category_id)->exists()) $error[] = 'Category id is not valid.';
        if ($this->request->condition_id && !Condition::where('id', $this->request->condition_id)->exists()) $error[] = 'Condition id is not valid.';
        if ($this->request->brand_id && !Brand::where('id', $this->request->brand_id)->exists()) $error[] = 'Brand id is not valid.';

        if ($error) return response()->json([
            'success' => false,
            'error'   => $error,
            'user'    => $this->user->getReturn()
        ]);

        $request = [
            'user_id'           => $this->user->id,
            'category_id'       => $this->request->category_id,
            'brand_id'          => isset($this->request->brand_id) ? $this->request->brand_id : null,
            'name'              => $this->request->name,
            'description'       => $this->request->description,
            'price'             => $this->request->price,
            'condition_id'      => $this->request->condition_id,
            'size'              => isset($this->request->size) ? $this->request->size : null,
            'shipping_fee'      => isset($this->request->shipping_fee) ? $this->request->shipping_fee : 0,
            'ships_from'        => isset($this->request->ships_from) ? $this->request->ships_from : '',
            'shipping_duration' => isset($this->request->shipping_duration) ? $this->request->shipping_duration : null
        ];

        $item = Item::create($request);

        if ($this->request->pictures) {
            $count = 1;
            $pictures = [];

            if (!File::exists(public_path("images/items/{$item->id}/"))) {
                File::makeDirectory(public_path("images/items/{$item->id}/"));
            }

            foreach ($this->request->pictures as $picture) {
                $filename  = time() . $count . '.' . $picture->getClientOriginalExtension();
                $path = public_path("images/items/{$item->id}/" . $filename);

                Image::make($picture->getRealPath())->save($path);
                $count++;
                $pictures[] = $filename;
            }

            $item->pictures = serialize($pictures);
            $item->save();
        }

        if ($this->user->followers->count() >= 1) {
            Notification::createNotif([
                'message'  => "{$this->user->username} added a new item '{$item->name}'.",
                'user_ids' => implode(',', $this->user->followers->keyBy('follower_user_id')->keys()->toArray()) . ',',
                'link'     => route('item_view', ['item_id' => $item->id]),
                'trigger'  => config('constant.NOTIFICATION_TRIGGER.user'),
                'user_id'  => $this->user->id
            ]);
        }

        $data = [
            'success' => true,
            'item'    => $item->getReturn(),
            'error'   => [],
            'user'    => $this->user->getReturn()
        ];

        return response()->json($data);
    }

    public function like()
    {
        $item = Item::find($this->request->item_id);

        if (!$item) return response()->json([
            'success' => false,
            'error'   => ['Item does not exist.'],
            'user'    => $this->user->getReturn()
        ]);

        if ($item->user_id == $this->user->id) {
            return response()->json([
                'success' => false,
                'error'   => ['Cannot like your own item.'],
                'user'    => $this->user->getReturn()
            ]);
        }

        if ($this->user->likes()->where('item_id', $item->id)->first()) {
            return response()->json([
                'success' => false,
                'error'   => ['Already liked this item.'],
                'user'    => $this->user->getReturn()
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
        
        $data = [
            'success' => true,
            'item'    => $item->getReturn(),
            'error'   => [],
            'user'    => $this->user->getReturn()
        ];

        return response()->json($data);
    }

    public function comment()
    {
        $item = Item::find($this->request->item_id);

        if (!$item) return response()->json([
            'success' => false,
            'error'   => ['Item does not exist.'],
            'user'    => $this->user->getReturn()
        ]);

        if (!$this->request->body) return response()->json([
            'success' => false,
            'error'   => ['Body is required.'],
            'user'    => $this->user->getReturn()
        ]);

        $request = [
            'item_id' => $item->id,
            'body'    => $this->request->body
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
        
        $data = [
            'success' => true,
            'item'    => $item->getReturn(),
            'error'   => [],
            'user'    => $this->user->getReturn()
        ];

        return response()->json($data);
    }

    public function delete()
    {
        $item = Item::find($this->request->item_id);

        if (!$item) return response()->json([
            'success' => false,
            'error'   => ['Item does not exist.'],
            'user'    => $this->user->getReturn()
        ]);

        if ($item->user_id != $this->user->id) {
            return response()->json([
                'success' => false,
                'error'   => ['Cannot delete this item because it does not belong to the user.'],
                'user'    => $this->user->getReturn()
            ]);
        }

        $item->delete();

        return redirect()->route('user_items');
    }

    public function search()
    {
        $keyword = $this->request->keyword;

        $items = collect();

        foreach (Item::where('name', 'like', "%$keyword%")->orWhere('description', 'like', "%$keyword%")->get() as $item) {
            $items[] = $item->getReturn();
        }

        $error = [];
        
        $data = [
            'success'    => true,
            'keyword'    => $keyword,
            'item_count' => $items->count(),
            'items'      => $items,
            'error'      => $error,
            'user'       => $this->user->getReturn()
        ];
        
        return response()->json($data);
    }

    public function viewByCategory()
    {
        $category = Category::find($this->request->category_id);

        if (!$category) return response()->json([
            'success' => false,
            'error'   => ['Category does not exist.'],
            'user'    => $this->user->getReturn()
        ]);

        $items = collect();
        foreach (Item::where('category_id', $category->id)->get() as $item) {
            $items[] = $item->getReturn();
        }

        $error = [];

        $data = [
            'success'    => true,
            'category'   => $category->name,
            'item_count' => $items->count(),
            'items'      => $items,
            'error'      => $error,
            'user'       => $this->user->getReturn()
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
                'error'   => $validator->errors()->all(),
                'user'    => $this->user->getReturn()
            ];

            return response()->json($data);
        }

        $item = Item::find($this->request->item_id);

        if (!$item) {
            $data = [
                'success' => false,
                'error'   => ['Item does not exist.'],
                'user'    => $this->user->getReturn()
            ];

            return response()->json($data);
        }

        if ($item->user->id == $this->user->id) {
            $data = [
                'success' => false,
                'error'   => ['Cannot buy your own item.'],
                'user'    => $this->user->getReturn()
            ];

            return response()->json($data);
        }

        if (!$item->isAvailable()) {
            $data = [
                'success' => false,
                'error'   => ['Item is not available.'],
                'user'    => $this->user->getReturn()
            ];

            return response()->json($data);
        }

        $purchase = [
            'item_id'          => $item->id,
            'shipping_address' => $this->request->shipping_address,
            'status'           => config('constant.PURCHASE_STATUS.pending')
        ];

        $this->user->purchases()->create($purchase);

        $payment = [
            'item_id' => $item->id,
            'amount'  => $item->price
        ];

        $this->user->payments()->create($payment);

        $item->update(['status' => config('constant.ITEM_STATUS.pending')]);

        Notification::createNotif([
            'message'  => "{$this->user->fullname} bought your item '{$item->name}'. Shipping address: {$this->request->shipping_address}",
            'user_ids' => $item->user->id . ",",
            'link'     => route('item_view', ['item_id' => $item->id]),
            'trigger'  => config('constant.NOTIFICATION_TRIGGER.user'),
            'user_id'  => $this->user->id
        ]);

        $data = [
            'success' => true,
            'item'    => $item->getReturn(),
            'error'   => [],
            'user'    => $this->user->getReturn()
        ];

        return response()->json($data);
    }

    public function ratePurchase()
    {
        $error = [];
        
        if (!$this->request->item_id) $error[] = 'Item id is required';
        if (!$this->request->rating) $error[] = 'Rating is required';
        
        if ($error) {
            $data = [
                'success' => false,
                'error'   => $error,
                'user'    => $this->user->getReturn()
            ];

            return response()->json($data);
        }
        
        $item = Item::find($this->request->item_id);

        if (!$item) {
            $data = [
                'success' => false,
                'error'   => ['Item does not exist.'],
                'user'    => $this->user->getReturn()
            ];

            return response()->json($data);
        }

        $is_buyer = $this->user->purchases()->where('item_id', $item->id)->exists();

        if (!$is_buyer) {
            $data = [
                'success' => false,
                'error'   => ['You cannot rate this item. You are not the item buyer.'],
                'user'    => $this->user->getReturn()
            ];

            return response()->json($data);
        }

        $purchase = $this->user->purchases()->where('item_id', $item->id)->first();

        if ($purchase->status == config('constant.PURCHASE_STATUS.complete')) {
            $data = [
                'success' => false,
                'error'   => ['Cannot rate. Purchase has already been completed.'],
                'user'    => $this->user->getReturn()
            ];

            return response()->json($data);
        }

        $this->user->purchase_ratings()->create([
            'item_id' => $item->id,
            'rating'  => $this->request->rating,
            'message' => isset($this->request->message) ? $this->request->message : null
        ]);

        $purchase->update(['status' => config('constant.PURCHASE_STATUS.complete')]);
        $item->update(['status' => config('constant.ITEM_STATUS.sold')]);

        Notification::createNotif([
            'message'  => "{$this->user->fullname} has rated the purchase on your item '{$item->name}'",
            'user_ids' => $item->user->id . ",",
            'link'     => route('item_view', ['item_id' => $item->id]),
            'trigger'  => config('constant.NOTIFICATION_TRIGGER.user'),
            'user_id'  => $this->user->id
        ]);

        $item->user->debits()->create([
            'amount'  => $item->price,
            'type'    => '',
            'item_id' => $item->id
        ]);

        $data = [
            'success' => true,
            'item'    => $item->getReturn(),
            'error'   => [],
            'user'    => $this->user->getReturn()
        ];

        return response()->json($data);
    }

    public function offer()
    {
        $error = [];
        if (!$this->request->item_id) $error[] = 'Item id is required';
        if (!$this->request->value) $error[] = 'Value is required';

        if ($this->request->item_id) {
            $item = Item::find($this->request->item_id);
            if (!$item) $error[] = 'Item id is invalid';
        }

        if ($error) {
            return response()->json([
                'success' => false,
                'error' => $error,
                'user' => $this->user->getReturn()
            ]);
        }

        $this->user->offers()->create([
            'item_id' => $item->id,
            'value' => $this->request->value
        ]);

        $data = [
            'success' => true,
            'item'    => $item->getReturn(),
            'error'   => [],
            'user'    => $this->user->getReturn()
        ];

        return response()->json($data);
    }
}
