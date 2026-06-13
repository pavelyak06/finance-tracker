<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::where('user_id', 1)->with('user');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $sortBy = $request->get('sort_by', 'date');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense',
            'description' => 'nullable|string|max:1000',
            'date' => 'required|date',
        ]);

        $validated['user_id'] = 1;

        $transaction = Transaction::create($validated);

        return response()->json([
            'success' => true,
            'data' => $transaction->load('user')
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $transaction = Transaction::where('user_id', 1)->with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $transaction = Transaction::where('user_id', 1)->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'type' => 'sometimes|in:income,expense',
            'description' => 'nullable|string|max:1000',
            'date' => 'sometimes|date',
        ]);

        $transaction->update($validated);

        return response()->json([
            'success' => true,
            'data' => $transaction->fresh('user')
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $transaction = Transaction::where('user_id', 1)->findOrFail($id);
        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully'
        ]);
    }
}