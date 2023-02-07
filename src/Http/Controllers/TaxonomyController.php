<?php

namespace Eminiarts\Aura\Http\Controllers;

use Eminiarts\Aura\Http\Requests\StoreTaxonomyRequest;
use Eminiarts\Aura\Http\Requests\UpdateTaxonomyRequest;
use Eminiarts\Aura\Models\Taxonomy;

class TaxonomyController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Taxonomy  $taxonomy
     * @return \Illuminate\Http\Response
     */
    public function destroy(Taxonomy $taxonomy)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Taxonomy  $taxonomy
     * @return \Illuminate\Http\Response
     */
    public function edit(Taxonomy $taxonomy)
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Taxonomy  $taxonomy
     * @return \Illuminate\Http\Response
     */
    public function show(Taxonomy $taxonomy)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreTaxonomyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTaxonomyRequest $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTaxonomyRequest  $request
     * @param  \App\Models\Taxonomy  $taxonomy
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTaxonomyRequest $request, Taxonomy $taxonomy)
    {
        //
    }
}
