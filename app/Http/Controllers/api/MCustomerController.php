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
    public function index(Request $request)
    {
        if (!auth('api')->check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        try {
            $query = MasterCustomerModel::query();

            // Search by kode customer
            if ($request->filled('custidkeyword')) {
                $query->where('kode_customer', 'LIKE', '%' . $request->custidkeyword . '%');
            }

            // Search by nama customer
            if ($request->filled('custnamekeyword')) {
                $query->where('nama_customer', 'LIKE', '%' . $request->custnamekeyword . '%');
            }

            // Global search (kode customer & nama customer)
            if ($request->filled('keyword')) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->where('kode_customer', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('nama_customer', 'LIKE', '%' . $keyword . '%');
                });
            }

            // Sort options
            $sortBy = $request->input('sortby', 'created_at');
            $sortOrder = $request->input('sortorder', 'desc');

            $allowedSortColumns = [
                'id_customer',
                'kode_customer',
                'nama_customer',
                'jenis_customer',
                'alamat',
                'telepon',
                'created_at',
                'updated_at'
            ];

            if (in_array($sortBy, $allowedSortColumns)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = $request->input('per_page', 10);
            $currentPage = $request->input('page', 1);

            $customers = $query->paginate($perPage, ['*'], 'page', $currentPage);

            if ($customers->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data customer kosong',
                    'data' => [],
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_page' => 0,
                    'has_next_page' => false,
                    'has_prev_page' => false,
                    'from' => null,
                    'to' => null
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data customer berhasil diambil',
                'data' => MastercustomerResource::collection($customers->items()),
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'total_page' => $customers->lastPage(),
                'has_next_page' => $customers->hasMorePages(),
                'has_prev_page' => $customers->currentPage() > 1,
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
                'search_params' => [
                    'custidkeyword' => $request->input('custidkeyword'),
                    'custnamekeyword' => $request->input('custnamekeyword'),
                    'keyword' => $request->input('keyword'),
                    'sortby' => $request->input('sortby'),
                    'sortorder' => $request->input('sortorder')
                ]
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
        if (!auth('api')->check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
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
    public function show($id)
    {
        try {
            $customer = MasterCustomerModel::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data customer tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data customer berhasil diambil',
                'data' => new MastercustomerResource($customer)
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
     * Update the specified resource in storage.
     */
    public function update(MastercustomerRequest $request, $id)
    {
        try {
            $customer = MasterCustomerModel::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data customer tidak ditemukan'
                ], 404);
            }

            $updateResult = $customer->update($request->validated());

            if (!$updateResult) {
                throw new \Exception('Gagal melakukan update ke database');
            }

            $updatedCustomer = MasterCustomerModel::find($id);

            return response()->json([
                'status' => true,
                'message' => 'Data customer berhasil diupdate',
                'data' => [
                    'id_customer' => $updatedCustomer->id_customer,
                    'kode_customer' => $updatedCustomer->kode_customer,
                    'nama_customer' => $updatedCustomer->nama_customer,
                    'jenis_customer' => $updatedCustomer->jenis_customer,
                    'alamat' => $updatedCustomer->alamat,
                    'telepon' => $updatedCustomer->telepon,
                    'created_at' => $updatedCustomer->created_at,
                    'updated_at' => $updatedCustomer->updated_at,
                ]
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
    public function destroy($id)
    {
        try {
            $customer = MasterCustomerModel::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data customer tidak ditemukan'
                ], 404);
            }

            $customer->delete();

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
