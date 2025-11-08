<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MastercustomerRequest;
use App\Http\Resources\MastercustomerResource;
use App\Models\MasterCustomerModel;
use Illuminate\Http\Request;

class MCustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $customers = MasterCustomerModel::paginate(10);

            if ($customers->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data customer kosong',
                    'master_customers' => []
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data customer berhasil diambil',
                'master_customers' => MastercustomerResource::collection($customers)->response()->getData()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MastercustomerRequest $request)
    {
        try {
            $customer = MasterCustomerModel::create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Data customer berhasil dibuat',
                'data' => new MastercustomerResource($customer)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat data customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MasterCustomerModel $mcustomer)
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Data customer berhasil ditemukan',
                'data' => new MastercustomerResource($mcustomer)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data customer tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MastercustomerRequest $request, MasterCustomerModel $mcustomer)
    {
        try {
            $mcustomer->update($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Data customer berhasil diupdate',
                'data' => new MastercustomerResource($mcustomer)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengupdate data customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MasterCustomerModel $mcustomer)
    {
        try {
            $mcustomer->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data customer berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
