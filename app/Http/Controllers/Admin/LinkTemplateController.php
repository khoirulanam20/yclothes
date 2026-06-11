<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LinkTemplateService;

class LinkTemplateController extends Controller
{
    public function __construct(private LinkTemplateService $linkTemplates) {}

    public function index()
    {
        return response()->json([
            'groups' => $this->linkTemplates->groups(),
        ]);
    }
}
