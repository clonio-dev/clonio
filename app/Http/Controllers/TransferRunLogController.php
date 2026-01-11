<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferRunLogRequest;
use App\Http\Requests\UpdateTransferRunLogRequest;
use App\Models\TransferRunLog;

class TransferRunLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransferRunLogRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(TransferRunLog $transferRunLog): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransferRunLog $transferRunLog): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransferRunLogRequest $request, TransferRunLog $transferRunLog): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransferRunLog $transferRunLog): void
    {
        //
    }
}
