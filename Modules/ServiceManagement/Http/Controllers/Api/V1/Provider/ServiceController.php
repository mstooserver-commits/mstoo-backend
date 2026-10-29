<?php

namespace Modules\ServiceManagement\Http\Controllers\Api\V1\Provider;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Support\MediaStorage;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\ReviewModule\Entities\Review;
use Modules\ServiceManagement\Entities\Service;
use Str;
use Illuminate\Support\Facades\Log;


class ServiceController extends Controller
{
    private Service $service;
    private Review $review;
    private SubscribedService $subscribed_service;

    public function __construct(Service $service, Review $review, SubscribedService $subscribed_service)
    {
        $this->service = $service;
        $this->review = $review;
        $this->subscribed_service = $subscribed_service;
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'required|in:subscribed,unsubscribed,all',
            'zone_id' => 'uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $ids = $this->subscribed_service->where('provider_id', $request->user()->provider->id)
            ->when($request->has('status') && $request['status'] != 'all', function ($query) use ($request) {
                if ($request['status'] == 'subscribed') {
                    return $query->where(['is_subscribed' => 1]);
                } else {
                    return $query->where(['is_subscribed' => 0]);
                }
            })->pluck('sub_category_id')->toArray();

        $services = $this->service->with(['category.zonesBasicInfo'])->latest()
            ->whereIn('sub_category_id', $ids)
            ->orWhereIn('category_id', $ids)
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        if (count($services) < 1) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }

        return response()->json(response_formatter(DEFAULT_200, $services), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @param string $service_id
     * @return JsonResponse
     */
    public function review(Request $request, string $service_id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'required|in:active,inactive,all'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $reviews = $this->review->with(['provider', 'customer'])->where('service_id', $service_id)->ofStatus(1)->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        $rating_group_count = DB::table('reviews')->where('service_id', $service_id)
            ->select('review_rating', DB::raw('count(*) as total'))
            ->groupBy('review_rating')
            ->get();

        $total_rating = 0;
        $rating_count = 0;
        foreach ($rating_group_count as $count) {
            $total_rating += round($count->review_rating * $count->total, 2);
            $rating_count += $count->total;
        }

        $rating_info = [
            'rating_count' => $rating_count,
            'average_rating' => round(divnum($total_rating, $rating_count), 2),
            'rating_group_count' => $rating_group_count,
        ];

        if ($reviews->count() > 0) {
            return response()->json(response_formatter(DEFAULT_200, ['reviews' => $reviews, 'rating' => $rating_info]), 200);
        }

        return response()->json(response_formatter(DEFAULT_404), 200);
    }


    /**
     * Show the specified resource.
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $service = $this->service->where('id', $id)->with(['category.children', 'variations'])->first();
        if (isset($service)) {
            $service = self::variations_react_format($service);
            return response()->json(response_formatter(DEFAULT_200, $service), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('servicemanagement::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function status_update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:1,0',
            'sub_category_ids' => 'required|array',
            'sub_category_ids.*' => 'uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $this->subscribed_service->whereIn('sub_category_id', $request['sub_category_ids'])->update(['is_subscribed' => $request['status']]);

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    private function variations_react_format($service)
    {
        $variants = collect($service['variations'])->pluck('variant_key')->unique();
        $storage = [];
        foreach ($variants as $variant) {
            $formatting = [];
            $filtered = $service['variations']->where('variant_key', $variant);
            $formatting['variationName'] = $variant;
            $formatting['variationPrice'] = $filtered->first()->price;
            foreach ($filtered as $single_variant) {
                $formatting['zoneWiseVariations'][] = [
                    'id' => $single_variant['zone_id'],
                    'price' => $single_variant['price']
                ];
            }
            $storage[] = $formatting;
        }
        $service['variations_react_format'] = $storage;
        return $service;
    }

    /**
     * Show the specified resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'string' => 'required',
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $keys = explode(' ', base64_decode($request['string']));

        $service = $this->service->where(function ($query) use ($keys) {
            foreach ($keys as $key) {
                $query->orWhere('name', 'LIKE', '%' . $key . '%');
            }
            })
            ->when($request->has('status') && $request['status'] != 'all', function ($query) use ($request) {
                if ($request['status'] == 'active') {
                    return $query->where(['is_active' => 1]);
                } else {
                    return $query->where(['is_active' => 0]);
                }
            })
            ->with(['category.zonesBasicInfo'])->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        if (count($service) > 0) {
            return response()->json(response_formatter(DEFAULT_200, $service), 200);
        }
        return response()->json(response_formatter(DEFAULT_204, $service), 200);
    }


    // custom functions by naresh
    public function getallservice(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'string' => 'string',
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $categories = DB::table('categories')->where('parent_id', '!=' , '0')->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');
        if (count($categories) > 0) {
            return response()->json(response_formatter(DEFAULT_200, $categories), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    public function getallcat(Request $request): JsonResponse
    {

        $categories = DB::table('categories')->where('parent_id', '=' , '0')->get();
        if (count($categories) > 0) {
            return response()->json(response_formatter(DEFAULT_200, $categories), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    public function getallsubcat(Request $request): JsonResponse
    {

        $categories = DB::table('categories')->where('parent_id', '!=' , '0')->get();
        if (count($categories) > 0) {
            return response()->json(response_formatter(DEFAULT_200, $categories), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    public function getallsubcatbyid(Request $request, $id): JsonResponse
    {

        $categories = DB::table('categories')->where('parent_id', '=' , $id)->get();
        if (count($categories) > 0) {
            return response()->json(response_formatter(DEFAULT_200, $categories), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }


    public function getfields(Request $request): JsonResponse
    {

        $dropdownData = [
            'vehicle_type' => ['car', 'motorcycle', 'truck'],
            'car_brand' => ['tata', 'hondaY', 'hyundai'],
            'model_year' => ['2015', '2016', '2017','2018','2019','2020'],
            'fuel_type' => ['petrol', 'gasoline', 'diesel','electric'],
            'transmission' => ['automatic', 'manual'],
            'condition' => ['new', 'like new', 'used'],
            'service_type' => ['home services', 'beauty & wellness', 'professional services'],
        ];

        return response()->json(['data' => $dropdownData]);
    }



    public function add_service(Request $request): JsonResponse
    {

        $request->validate([
            'name' => 'required|max:191',
            'sub_category_id' => 'required|uuid',
            'cover_image' => 'required',
            'description' => 'required',
            'price' => 'required',

        ]);

        $service = $this->service;

        $cat = DB::table('categories')->where('id', $request->sub_category_id)->first();

        if (!$cat) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        if ($request->is_featured === 'yes') {
            $paymentError = $this->verifyFeaturedAdPayment($request, $cat);
            if ($paymentError) return $paymentError;
        }

        $service->category_id = $cat->parent_id;

        $service->name = $request->name;
        $service->sub_category_id = $request->sub_category_id;
        $service->short_description = $request->short_description;
        $service->description = $request->description;
        $service->availability = $request->availability;


		// save lat long
        $service->latitude = $request->latitude;
        $service->longitude = $request->longitude;
		//

		try {
            $cover_image = MediaStorage::storeImage($request->cover_image, 'service');
        } catch (\InvalidArgumentException $exception) {
            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'cover_image', 'message' => $exception->getMessage()],
            ]), 400);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'cover_image', 'message' => translate('failed_to_upload_cover_image')],
            ]), 400);
        }

        $service->cover_image = $cover_image;
        $service->thumbnail = $cover_image;
        $uploadedImages = [];
        $imageFiles = $request->file('images', []);
        if (!is_array($imageFiles)) {
            $imageFiles = [$imageFiles];
        }
        foreach ($imageFiles as $image) {
            if (!$image) {
                continue;
            }
            try {
                $uploadedImages[] = MediaStorage::storeUploadedFile($image, 'service');
            } catch (\InvalidArgumentException $exception) {
                return response()->json(response_formatter(DEFAULT_400, null, [
                    ['error_code' => 'images', 'message' => $exception->getMessage()],
                ]), 400);
            }
        }
        // cover_image is the first product photo. images[] contains only
        // optional additional gallery photos, so a single-photo ad is valid.
        $service->thumbnails = json_encode($uploadedImages);
        $service->added_by = $request->user()->id;
        $service->is_featured = $request->is_featured;

        if ($request->cat_name == "vehicle") {
	        $service->vehicle_type = $request->vehicle_type;
	        $service->vehicle_brand = $request->vehicle_brand;
	        $service->model_year = $request->model_year;
	        $service->mileage = $request->mileage;
	        $service->fuel_type = $request->fuel_type;
	        $service->transmission = $request->transmission;
	        $service->condition = $request->condition;
	        $service->location = $request->location;
	        $service->availability_date = $request->availability_date;
	        $service->contact_info = $request->contact_info;
	        $service->deposits = $request->deposits;
	        $service->doc_required = $request->doc_required;
	        $service->additional_info = $request->additional_info;
	        $service->delivery_pickup = $request->delivery_pickup;
	        $service->safety = $request->safety_guidelines;
	        $service->t_and_c = $request->t_and_c;
        }

        if ($request->cat_name == "service") {
	        $service->service_type = $request->service_type;
	        $service->location = $request->location;
	        $service->availability_date = $request->availability_date;
	        $service->contact_info = $request->contact_info;
	        $service->deposits = $request->deposits;
	        $service->doc_required = $request->doc_required;
	        $service->additional_info = $request->additional_info;
	        $service->delivery_pickup = $request->delivery_pickup;
	        $service->safety = $request->safety;
	        $service->t_and_c = $request->t_and_c;
        }

        if ($request->cat_name == "equipment") {
	        $service->equipment_type = $request->equipment_type;
	        $service->equipment_brand= $request->equipment_brand;
	        $service->condition= $request->condition;
	        $service->power_source= $request->power_source;
	        $service->weight= $request->weight;
	        $service->dimensions= $request->dimensions;
	        $service->location = $request->location;
	        $service->availability_date = $request->availability_date;
	        $service->contact_info = $request->contact_info;
	        $service->deposits = $request->deposits;
	        $service->doc_required = $request->doc_required;
	        $service->additional_info = $request->additional_info;
	        $service->delivery_pickup = $request->delivery_pickup;
	        $service->safety = $request->safety;
	        $service->t_and_c = $request->t_and_c;
        }

        if ($request->cat_name == "property") {
	        $service->property_type = $request->property_type;
	        $service->bedrooms= $request->bedrooms;
	        $service->bathrooms= $request->bathrooms;
	        $service->square_footage= $request->square_footage;
	        $service->furnished= $request->furnished;
	        $service->location = $request->location;
	        $service->availability_date = $request->availability_date;
	        $service->contact_info = $request->contact_info;
	        $service->deposits = $request->deposits;
	        $service->doc_required = $request->doc_required;
	        $service->additional_info = $request->additional_info;
	        $service->utilities = $request->utilities;
	        $service->pets = $request->pets;
	        $service->t_and_c = $request->t_and_c;
        }

        if ($request->cat_name == "furniture") {
	        $service->furniture_type = $request->furniture_type;
	        $service->furniture_brand= $request->furniture_brand;
	        $service->condition= $request->condition;
	        $service->location = $request->location;
	        $service->availability_date = $request->availability_date;
	        $service->contact_info = $request->contact_info;
	        $service->deposits = $request->deposits;
	        $service->doc_required = $request->doc_required;
	        $service->additional_info = $request->additional_info;
	        $service->delivery_pickup = $request->delivery_pickup;
	        $service->t_and_c = $request->t_and_c;
        }

        if ($request->cat_name == "electronic") {
	        $service->electronic_type = $request->equipment_type;
	        $service->electronic_brand= $request->equipment_brand;
	        $service->model_year= $request->model_year;
	        $service->condition= $request->condition;
	        $service->operating_system= $request->operating_system;
	        $service->screen_size= $request->screen_size;
	        $service->storage_capacity= $request->storage_capacity;
	        $service->camera_resolution= $request->camera_resolution;
	        $service->connectivity= $request->connectivity;

	        $service->location = $request->location;
	        $service->availability_date = $request->availability_date;
	        $service->contact_info = $request->contact_info;
	        $service->deposits = $request->deposits;
	        $service->doc_required = $request->doc_required;
	        $service->additional_info = $request->additional_info;
	        $service->delivery_pickup = $request->delivery_pickup;
	        $service->t_and_c = $request->t_and_c;
        }

        if ($request->cat_name == "cloth") {
	        $service->cloth_type = $request->cloth_type;
	        $service->cloth_brand= $request->cloth_brand;
	        $service->cloth_size= $request->cloth_size;
	        $service->condition= $request->condition;
	        $service->operating_system= $request->operating_system;
	        $service->screen_size= $request->screen_size;
	        $service->storage_capacity= $request->storage_capacity;
	        $service->camera_resolution= $request->camera_resolution;
	        $service->connectivity= $request->connectivity;
	        $service->location = $request->location;
	        $service->availability_date = $request->availability_date;
	        $service->contact_info = $request->contact_info;
	        $service->deposits = $request->deposits;
	        $service->doc_required = $request->doc_required;
	        $service->additional_info = $request->additional_info;
	        $service->delivery_pickup = $request->delivery_pickup;
	        $service->t_and_c = $request->t_and_c;
        }

		if ($request->is_featured == "yes") {
	        $service->order_id = $request->order_id;
	        $service->payment_id = $request->payment_id;
	        $service->signature = $request->signature;
        }

        $service->save();

        $zone = DB::table('zones')->first();

		if($request->rent_duration){
			$var = DB::table('variations')->insert(
				[
					'variant' => $request->rent_duration,
					'variant_key' => $request->rent_duration,
					'zone_id' => $zone->id,
					'price' => $zone->id,
					'zone_id' => $zone->id,
					'price' => $request->price,
					'service_id' => $service->id,
				]
			);
		}
		else{
			$var = DB::table('variations')->insert(
				[
					'variant' => 'per day',
					'variant_key' => 'per day',
					'zone_id' => $zone->id,
					'price' => $zone->id,
					'zone_id' => $zone->id,
					'price' => $request->price,
					'service_id' => $service->id,
				]
			);

		}
        
        return response()->json(response_formatter(DEFAULT_200, $var), 200);

        return back();
    }


    public function myservices(Request $request): JsonResponse
    {

    	$renter_id = $request->user()->id;
        $services = DB::table('services')
        			->join('variations', 'services.id', '=', 'variations.service_id')
        			->where('added_by', '=' , $renter_id)
        			->select('services.*', 'variations.price')
        			->get();	
        if (count($services) > 0) {
            return response()->json(response_formatter(DEFAULT_200, $services), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    public function getservice(Request $request, $id): JsonResponse
    {
        $service = DB::table('services')
        			->join('variations', 'services.id', '=', 'variations.service_id')
        			->select('services.*', 'variations.price')
        			->where('services.id','=', $id)
        			->first();

       	$cover_image_url = "";
		$thumbnail_url = "";

		if($service->cover_image){
        	$url = url('/storage/app/public/service/'.$service->cover_image);
	        if (@getimagesize($url)) {
	           $cover_image_url = $url;
	       	}
		}	
		if($service->thumbnail){
        	$url = url('/storage/app/public/service/'.$service->thumbnail);
	        if (@getimagesize($url)) {
	           $thumbnail_url = $url;
	       	}
		}

		$images = array(
	      'cover_image' => $cover_image_url,
	      'thumbnail' => $thumbnail_url,
	      );

		$response = [
	        'service'=>$service,
	        'images' => $images
	    ];

        if ($service != null) {
            return response()->json(response_formatter(DEFAULT_200, $response), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }


    public function update_service(Request $request, $id): JsonResponse
    {

		  //       $request->validate([
		  //           'name' => 'required|max:191',
		  //           'sub_category_id' => 'required|uuid',
		  //           'cover_image' => 'required',
		  //           'description' => 'required',
		  //           'thumbnail' => 'required',
		  //           'price' => 'required',
		  //           'availability' => 'required',

		  //       ]);


		  //       $cat = DB::table('categories')->where('id', $request->sub_category_id)->first();

				// $img = $request->cover_image;
				// $file = base64_decode($img);
				// $folderName = storage_path('app/public/service/');
				// $safeName = Str::random(10).'.'.'jpg';
				// $folder = file_put_contents($folderName.$safeName, $file);
				// $cover_image = $safeName;

				// $thumb = $request->thumbnail;
				// $thumbfile = base64_decode($thumb);
				// $thumbfolderName = storage_path('app/public/service/');
				// $thumbsafename = Str::random(10).'.'.'jpg';
				// $thumbfolder = file_put_contents($thumbfolderName.$thumbsafename, $thumbfile);
				// $thumbnail = $thumbsafename;


		  //       DB::table('variations')
		  //       ->where('service_id', $id)
		  //       ->update([
		  //       	'price' => $request->price,
		  //       ]);


		  //       DB::table('services')
		  //       ->where('id', $id)
		  //       ->update([
		  //       	'category_id' => $cat->parent_id,
		  //       	'name' => $request->name,
		  //       	'sub_category_id' => $request->sub_category_id,
		  //       	'description' => $request->short_description,
		  //       	'availability' => $request->availability,
		  //       	'thumbnail' => $thumbnail,
		  //       ]);
		  //       return response()->json(response_formatter(DEFAULT_200), 200);

		  //       return back();

	        $request->validate([
	            'name' => 'required|max:191',
	            'sub_category_id' => 'required|uuid',
	            'cover_image' => 'required',
	            'description' => 'required',
	            // 'thumbnail' => 'required',
	            'price' => 'required',

	        ]);
	        $cat = DB::table('categories')->where('id', $request->sub_category_id)->first();
            $existing = DB::table('services')->where('id', $id)->first();
            if (!$existing) {
                return response()->json(response_formatter(DEFAULT_204), 200);
            }

	        if($request->cover_image != ""){
	            try {
                    $cover_image = MediaStorage::storeImage($request->cover_image, 'service');
                } catch (\InvalidArgumentException $exception) {
                    return response()->json(response_formatter(DEFAULT_400, null, [
                        ['error_code' => 'cover_image', 'message' => $exception->getMessage()],
                    ]), 400);
                } catch (\Throwable $exception) {
                    report($exception);
                    return response()->json(response_formatter(DEFAULT_400, null, [
                        ['error_code' => 'cover_image', 'message' => translate('failed_to_upload_cover_image')],
                    ]), 400);
                }
	        } else {
                $cover_image = $existing->cover_image;
            }

	        if($request->file('images') != ""){
	            $uploadedImages = [];
	            foreach ($request->file('images') as $image) {
	            $imageName = MediaStorage::storeUploadedFile($image, 'service');
	            $uploadedImages[] = $imageName;
	            }
	        }

	        DB::table('services')
	        ->where('id', $id)
	        ->update([
	         'category_id' => $cat->parent_id,
	         'name' => $request->name,
	         'sub_category_id' => $request->sub_category_id,
	         'description' => $request->description,
	         'availability' => $request->availability,
	         'is_featured' => $request->is_featured,
	         'thumbnail' => $cover_image,
	         'cover_image' => $cover_image,
	         'thumbnails' => json_encode($uploadedImages),
	        ]);

	        $var = DB::table('variations')
	        ->where('service_id', $id)
	        ->update([
	         'price' => $request->price,
	        ]);

	        return response()->json(response_formatter(DEFAULT_200, $var), 200);

	        return back();
    }


    public function delete_service(Request $request, $id): JsonResponse
    {

        $delete = DB::table('services')->where('id', '=' , $id)->delete();
       	
       	$response=[
				   'message' => 'Service deleted successfully!',
				   'status' => 'success',
				];
        return response()->json($response, 200);
    }


    public function getfieldsbyid(Request $request, $id): JsonResponse
    {
    	$category = DB::table('categories')->where('id',$id)->first();
    	if ($category) {
    		$catname = $category->name;
    	}

    	$cloth = "Clothes";
    	$electronic = "Electronics";
    	$equipment = "Equipment";
    	$furniture = "Furniture";
    	$property = "Property";
    	$service = "Services";
    	$vehicle = "Vehicles";


    	$dropdownData = [];

    	if ($catname == $cloth) {

    	    $dropdownData = [
	            'category_name' => "cloth",
	            // 'product_type' => ['smartphone', 'laptop', 'camera'],
	            // 'brand_make' => ['apple', 'sony', 'samsung', 'canon', 'nikon'],
	            // 'model_year' => ['2015', '2016', '2017','2018','2019','2020'],
	            'condition' => ['new', 'like new', 'used'],
	            // 'operating_system' => ['ios', 'windows', 'linux'],
	            // 'screen_size' => ['5', '6', '7', '8', '9', '10']
				'featured_price' => "".$category->featured_price."",
				'featured_days' => "30 days"
        	];

    	}
    	if ($catname == $electronic) {
    	    Log::info("electronic");
    	    Log::info($catname);
    	    $dropdownData = [
	            'category_name' => "electronic",
	            'electronic_type' => ['smartphone', 'laptop', 'camera'],
	            'electronic_brand' => ['apple', 'sony', 'samsung', 'canon', 'nikon'],
	            'model_year' => ['2015', '2016', '2017','2018','2019','2020'],
	            'condition' => ['new', 'like new', 'used'],
	            'operating_system' => ['ios', 'windows', 'linux'],
	            'screen_size' => ['5', '6', '7', '8', '9', '10'],
				'featured_price' => "".$category->featured_price."",
				'featured_days' => "30 days"

        	];
    	}
    	if ($catname == $equipment) {
    	    Log::info("equipment");
    	    Log::info($catname);
    	    $dropdownData = [
	            'category_name' => "equipment",
	            'equipment_type' => ['fitness', 'industrial machinery', 'construction'],
	            'equipment_brand' => ['technogym', 'BH fitness', 'tunturi', 'cybex', 'precor'],
	            'power_source' => ['electric', 'diesel', 'gasoline'],
	            'condition' => ['new', 'like new', 'used'],
				'featured_price' => "".$category->featured_price."",
				'featured_days' => "30 days"
        	];
    	}
    	if ($catname == $furniture) {
    	    Log::info("furniture");
    	    Log::info($catname);
    	    $dropdownData = [
	            'category_name' => "furniture",
	            'furniture_type' => ['sofa', 'table', 'chair'],
	            'furniture_brand' => ['wooden street', 'bluewud', 'nilkamal', 'hometown', 'spacewood'],
	            'condition' => ['new', 'like new', 'used'],
				'featured_price' => "".$category->featured_price."",
				'featured_days' => "30 days"
        	];
    	}
    	if ($catname == $property) {
    	    Log::info("property");
    	    Log::info($catname);
    	    $dropdownData = [
	            'category_name' => "property",
	            'property_type' => ['apartment', 'house', 'condo'],
	            'bedrooms' => ['1', '2', '3', '4', '5'],
	            'bathrooms' => ['1', '2', '3','4','5','6'],
	            'furnished' => ['furnished', 'unfurnished', 'partially furnished'],
				'featured_price' => "".$category->featured_price."",
				'featured_days' => "30 days"
        	];
    	}
    	if ($catname == $service) {
    		Log::info("service");
    	    Log::info($catname);
    		$dropdownData = [
	            'category_name' => "service",
	            'service_type' => ['home services', 'beauty & wellness', 'professional services'],
				'featured_price' => "".$category->featured_price."",
				'featured_days' => "30 days"
        	];
    	
    	}
    	if ($catname == $vehicle) {
    		Log::info("vehicle");
    	    Log::info($catname);
    		$dropdownData = [
	            'category_name' => "vehicle",
	            'vehicle_type' => ['car', 'motorcycle', 'truck'],
	            'vehicle_brand' => ['tata', 'honda', 'hyundai'],
	            'model_year' => ['2015', '2016', '2017','2018','2019','2020'],
	            'fuel_type' => ['petrol', 'gasoline', 'diesel','electric'],
	            'transmission' => ['automatic', 'manual'],
	            'condition' => ['new', 'like new', 'used'],
				'featured_price' => "".$category->featured_price."",
				'featured_days' => "30 days"
        	];
    	
    	}
    	if ($catname == $cloth) {
    		Log::info("cloth");
    	    Log::info($catname);
    		$dropdownData = [
	            'category_name' => "cloth",
	            'cloth_type' => ['dress', 'suit', 'shoes'],
	            'cloth_brand' => ['zara', 'adidas', 'puma','h&m','reebok'],
	            'cloth_size' => ['s', 'm', 'l','xl','xxl'],
	            'condition' => ['new', 'like new', 'used'],
				'featured_price' => "".$category->featured_price."",
				'featured_days' => "30 days"
        	];
    	
    	}

        return response()->json(['data' => $dropdownData]);
    }


	// Get razorpay payment data (same credentials as booking / wallet)
	public function featured_ad_razorpay_order(Request $request): JsonResponse
    {
        $request->validate([
            'sub_category_id' => 'nullable|required_without:service_id|uuid',
            'service_id' => 'nullable|required_without:sub_category_id|uuid',
        ]);

        $service = null;
        if ($request->filled('service_id')) {
            $service = $this->service->newQuery()
                ->where('id', $request->service_id)
                ->where('added_by', $request->user()->id)
                ->first();
            if (!$service) {
                return response()->json(response_formatter(DEFAULT_404), 404);
            }
        }

        $subCategoryId = $service->sub_category_id ?? $request->sub_category_id;
        $subCategory = DB::table('categories')->where('id', $subCategoryId)->first();
        $category = $subCategory ? DB::table('categories')->where('id', $subCategory->parent_id)->first() : null;
        $amount = (int) round((float) ($category->featured_price ?? 0) * 100);

        if (!$category || $amount < 100 || !payment_method_allowed('razor_pay')) {
            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'featured_ad', 'message' => 'Featured ads are not available for this category'],
            ]), 400);
        }

        $config = ensure_razorpay_config();
        if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'razorpay_config', 'message' => 'Razorpay is not configured'],
            ]), 400);
        }

        try {
            $api = new \Razorpay\Api\Api($config['api_key'], $config['api_secret']);
            $order = $api->order->create([
                'amount' => $amount,
                'currency' => 'INR',
                'receipt' => 'fa' . substr(str_replace('-', '', (string) $request->user()->id), 0, 12) . time(),
                'payment_capture' => 1,
                'notes' => array_filter([
                    'sub_category_id' => $subCategoryId,
                    'service_id' => $service->id ?? null,
                    'user_id' => $request->user()->id,
                ]),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Featured ad Razorpay order failed', ['error' => $exception->getMessage()]);
            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'razorpay_order', 'message' => 'Unable to start featured ad payment'],
            ]), 400);
        }

        return response()->json(response_formatter(DEFAULT_200, [
            'key' => $config['api_key'],
            'order_id' => $order['id'] ?? null,
            'amount' => $amount,
            'currency' => 'INR',
        ]), 200);
    }

    public function activate_featured_ad(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'payment_id' => 'required|string',
            'order_id' => 'required|string',
            'signature' => 'required|string',
        ]);

        $service = $this->service->newQuery()
            ->where('id', $id)
            ->where('added_by', $request->user()->id)
            ->first();
        if (!$service) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $subCategory = DB::table('categories')->where('id', $service->sub_category_id)->first();
        if (!$subCategory) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $paymentError = $this->verifyFeaturedAdPayment($request, $subCategory);
        if ($paymentError) {
            return $paymentError;
        }

        $service->is_featured = 'yes';
        $service->order_id = $request->order_id;
        $service->payment_id = $request->payment_id;
        $service->signature = $request->signature;
        $service->save();

        return response()->json(response_formatter(DEFAULT_200, [
            'id' => $service->id,
            'is_featured' => $service->is_featured,
        ]), 200);
    }

    private function verifyFeaturedAdPayment(Request $request, object $subCategory): ?JsonResponse
    {
        $category = DB::table('categories')->where('id', $subCategory->parent_id)->first();
        $expectedAmount = (int) round((float) ($category->featured_price ?? 0) * 100);
        if ($expectedAmount < 100 || !$request->filled('payment_id')) {
            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'featured_payment', 'message' => 'A valid featured ad payment is required'],
            ]), 400);
        }
        if ($this->service->newQuery()->where('payment_id', $request->payment_id)->exists()) {
            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'featured_payment', 'message' => 'This payment has already been used'],
            ]), 400);
        }

        try {
            $config = ensure_razorpay_config();
            if (!$config) throw new \RuntimeException('Razorpay is not configured');
            $api = new \Razorpay\Api\Api($config['api_key'], $config['api_secret']);
            if ($request->filled('signature') && $request->filled('order_id')) {
                $api->utility->verifyPaymentSignature([
                    'razorpay_order_id' => $request->order_id,
                    'razorpay_payment_id' => $request->payment_id,
                    'razorpay_signature' => $request->signature,
                ]);
            }
            $payment = $api->payment->fetch($request->payment_id);
            $paymentOrderId = $payment['order_id'] ?? null;
            if (($request->filled('order_id') && $paymentOrderId !== $request->order_id)
                || (int) ($payment['amount'] ?? 0) !== $expectedAmount
                || !in_array($payment['status'] ?? null, ['captured', 'authorized'], true)) {
                throw new \RuntimeException('Featured ad payment verification failed');
            }
            if (!$request->filled('order_id') && $paymentOrderId) {
                $request->merge(['order_id' => $paymentOrderId]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Featured ad payment verification failed', ['error' => $exception->getMessage()]);
            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'featured_payment', 'message' => 'Featured ad payment verification failed'],
            ]), 400);
        }
        return null;
    }

	public function getpaymentgateway(Request $request): JsonResponse
    {
        $config = ensure_razorpay_config();
        if ($config && !empty($config['api_key'])) {
            return response()->json(response_formatter(DEFAULT_200, [
                'name' => 'razorpay',
                'key_id' => $config['api_key'],
                'mode' => $config['mode'] ?? razorpay_mode(),
            ]), 200);
        }

        // Legacy fallback table (if still populated with valid keys)
        $gateway = DB::table('payment_gateways')->first();
        if ($gateway) {
            return response()->json(response_formatter(DEFAULT_200, $gateway), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }


}
