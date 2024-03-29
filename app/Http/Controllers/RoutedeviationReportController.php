<?php

namespace App\Http\Controllers;

use App\Models\RoutedeviationReport;
use App\Models\DemoPolyline;
use App\Http\Requests\StoreRoutedeviationReportRequest;
use App\Http\Requests\UpdateRoutedeviationReportRequest;
use App\Models\PlayBackHistoryReport;
use App\Models\Routes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
// use App\Http\Service\AddressService;
use App\Service\AddressService;
// use App\Service\AddressService\get_address;

class RoutedeviationReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use AddressService;

    public function index()
    {

        // dd($data);
        // $routedeviation_data = RoutedeviationReport::get();
        // $polyline_data = DemoPolyline::first();
        // return view('report.routedeviation_report',compact('routedeviation_data','polyline_data'));
        // $from_date = date('Y-m-d H:i:s', strtotime('00:00:00'));
        $from_date = "2023-01-01 00:00:00";
        $to_date = date('Y-m-d H:i:s', strtotime('23:59:59'));
        return view('report.routedeviation_report', compact('from_date', 'to_date'));
    }

    public function getData(Request $request)
    {

        $data = ($request->all());
        $fromdate = date('Y-m-d H:i:s', strtotime($request->input('fromdate')));
        $todate = date('Y-m-d H:i:s', strtotime($request->input('todate')));
        // $address =  $request->input('active');
        $totalFilteredRecord = $totalDataRecord = $draw_val = "";
        $columns_list = array(
            0 => 'id'
        );

        $totalDataRecord = RoutedeviationReport::count();

        $totalFilteredRecord = RoutedeviationReport::whereBetween('created_at', [$fromdate, $todate])
            ->select('id')
            ->where('location_status',2)
            ->count();

        $limit_val = $request->input('length');
        $start_val = $request->input('start');
        $order_val = $columns_list[$request->input('order.0.column')];
        // 'desc' = $request->input('order.0.dir');

        $start = $request->input('start') + 1;

        if (empty($request->input('search.value'))) {
            $post_data = RoutedeviationReport::whereBetween('created_at', [$fromdate, $todate])
                ->where('location_status', 2)
                ->offset($start_val)
                ->limit($limit_val)
                ->orderBy($order_val, 'desc')
                ->select('id', 'route_name', 'vehicle_imei', 'vehicle_name', 'route_deviate_outtime', 'route_deviate_intime', 'route_out_location', 'route_in_location', 'route_out_lat', 'route_out_lng', 'route_in_lat', 'route_in_lng', DB::raw('SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(route_deviate_intime, route_deviate_outtime))) AS time_difference'))
                ->get();
        } else {

            $search_text = $request->input('search.value');
            $post_data =  RoutedeviationReport::where('id', 'LIKE', "%{$search_text}%")
                ->where('location_status', 2)
                ->orWhere('vehicle_imei', 'LIKE', "%{$search_text}%")
                ->orWhere('vehicle_name', 'LIKE', "%{$search_text}%")
                ->orWhere('route_name', 'LIKE', "%{$search_text}%")
                ->offset($start_val)
                ->limit($limit_val)
                ->orderBy($order_val, 'desc')
                ->get();

            $totalFilteredRecord = RoutedeviationReport::where('id', 'LIKE', "%{$search_text}%")
                ->where('location_status', 2)
                ->orWhere('vehicle_imei', 'LIKE', "%{$search_text}%")
                ->orWhere('vehicle_name', 'LIKE', "%{$search_text}%")
                ->orWhere('route_name', 'LIKE', "%{$search_text}%")
                ->count();
        }
        // dd($post_data);

        if (!empty($post_data)) {
            foreach ($post_data  as $index =>  $data) {

                $serialNumber = $start + $index;
                $start_time = Carbon::parse($data->route_deviate_outtime)->timestamp;
                $end_time = Carbon::parse($data->route_deviate_intime)->timestamp;
                $time_difference = substr($data->time_difference, 0, 8);
                $route_name = $data->route_name;
                $route_id = $data->id;
                $edit = '<button type="button" class="btn btn-success showModal"  onclick="route_deviation_data(' . "$start_time" . "," . "$end_time" . "," . "'$route_name'" . "," . "'$route_id'" . ');" >Map View</button>';

                $array_data[] = array(
                    'S No' => $serialNumber,
                    'vehicle_name' => $data->vehicle_name,
                    'route_name' => $data->route_name,
                    'vehicle_imei' => $data->vehicle_imei,
                    'route_deviate_outtime' => $data->route_deviate_outtime,
                    'route_deviate_intime' => $data->route_deviate_intime,
                    'route_out_address' => $data->route_out_location,
                    'route_in_address' => $data->route_in_location,
                    'time_difference' => $time_difference,
                    'Action' => $edit
                );
            }

            if (!empty($array_data)) {
                $draw_val = $request->input('draw');
                $get_json_data = array(
                    "draw"            => intval($draw_val),
                    "recordsTotal"    => intval($totalDataRecord),
                    "recordsFiltered" => intval($totalFilteredRecord),
                    "data"            => $array_data
                );

                echo json_encode($get_json_data);
            }
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoutedeviationReportRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(RoutedeviationReport $routedeviationReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RoutedeviationReport $routedeviationReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoutedeviationReportRequest $request, RoutedeviationReport $routedeviationReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoutedeviationReport $routedeviationReport)
    {
        //
    }

    public function playdata(Request $request)
    {
        $start_time = $request->start_time; // Replace this with your timestamp
        $end_time = $request->end_time;
        $route_name = $request->route_name;
        $dateTime = Carbon::createFromTimestamp($start_time);
        $dateTime1 = Carbon::createFromTimestamp($end_time);
        $formattedDateTime = $dateTime->format('Y-m-d H:i:s');
        $formattedDateTime1 = $dateTime1->format('Y-m-d H:i:s');
        $route_id =  $request->route_id;
        $get_latlng = RoutedeviationReport::select('route_out_lat', 'route_out_lng', 'route_in_lat', 'route_in_lng', 'vehicle_imei')
            ->where('id', '=', "$route_id")->first();
        $route_out_address = $this->get_address($get_latlng->route_out_lat, $get_latlng->route_out_lng);
        $route_in_address = $this->get_address($get_latlng->route_in_lat, $get_latlng->route_in_lng);
        $query['location'] = array('route_out_address' => $route_out_address, 'route_in_address' => $route_in_address);
        $query['playback'] = PlayBackHistoryReport::select('latitude', 'longitude')
            ->where('device_imei', '=', $get_latlng->vehicle_imei)
            ->whereBetween('device_datetime', [$formattedDateTime, $formattedDateTime1])->get();
        $query['route_polyline'] = Routes::select('route_polyline AS polyline', 'routename AS route_name')
            ->where('routename', '=', "$route_name")->get();
        return $query;
    }
}
