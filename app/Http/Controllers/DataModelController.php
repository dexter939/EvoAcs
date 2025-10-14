<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataModelController extends Controller
{
    public function index(Request $request)
    {
        $protocolFilter = $request->get('protocol', 'all');
        $vendorFilter = $request->get('vendor', 'all');
        
        $query = DB::table('tr069_data_models as dm')
            ->select(
                'dm.id',
                'dm.vendor',
                'dm.model_name',
                'dm.firmware_version',
                'dm.protocol_version',
                'dm.created_at',
                'dm.updated_at',
                DB::raw('COUNT(CASE WHEN p.is_object = true THEN 1 END) as objects_count'),
                DB::raw('COUNT(CASE WHEN p.is_object = false THEN 1 END) as parameters_count'),
                DB::raw('COUNT(p.id) as total_count')
            )
            ->leftJoin('tr069_parameters as p', 'dm.id', '=', 'p.data_model_id')
            ->groupBy('dm.id', 'dm.vendor', 'dm.model_name', 'dm.firmware_version', 'dm.protocol_version', 'dm.created_at', 'dm.updated_at');
        
        if ($protocolFilter !== 'all') {
            $query->where('dm.protocol_version', $protocolFilter);
        }
        
        if ($vendorFilter !== 'all') {
            $query->where('dm.vendor', $vendorFilter);
        }
        
        $dataModels = $query->get();
        
        $vendors = DB::table('tr069_data_models')->distinct()->pluck('vendor');
        $protocols = DB::table('tr069_data_models')->distinct()->pluck('protocol_version');
        
        return view('acs.data-models', compact('dataModels', 'vendors', 'protocols', 'protocolFilter', 'vendorFilter'));
    }
    
    public function showParameters(Request $request, $id)
    {
        $dataModel = DB::table('tr069_data_models')->where('id', $id)->first();
        
        if (!$dataModel) {
            abort(404, 'Data Model not found');
        }
        
        $searchTerm = $request->get('search', '');
        $accessFilter = $request->get('access', 'all');
        $typeFilter = $request->get('type', 'all');
        
        $query = DB::table('tr069_parameters')
            ->where('data_model_id', $id);
        
        if (!empty($searchTerm)) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('parameter_path', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('parameter_name', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        if ($accessFilter !== 'all') {
            $query->where('access_type', $accessFilter);
        }
        
        if ($typeFilter === 'objects') {
            $query->where('is_object', true);
        } elseif ($typeFilter === 'parameters') {
            $query->where('is_object', false);
        }
        
        $parameters = $query->orderBy('parameter_path')->paginate(50);
        
        return response()->json([
            'data_model' => $dataModel,
            'parameters' => $parameters,
            'filters' => [
                'search' => $searchTerm,
                'access' => $accessFilter,
                'type' => $typeFilter
            ]
        ]);
    }
}
