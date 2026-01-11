<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDatabaseConnectionRequest;
use App\Http\Requests\UpdateDatabaseConnectionRequest;
use App\Models\DatabaseConnection;

class DatabaseConnectionController extends Controller
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
    public function store(StoreDatabaseConnectionRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(DatabaseConnection $databaseConnection): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DatabaseConnection $databaseConnection): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDatabaseConnectionRequest $request, DatabaseConnection $databaseConnection): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DatabaseConnection $databaseConnection): void
    {
        //
    }
}
