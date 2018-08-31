<?php

namespace Orlyapps\NovaBelongsToDepend\Http\Controllers;

use Illuminate\Support\HtmlString;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use Orlyapps\NovaBelongsToDepend\NovaBelongsToDepend;

class FieldController extends Controller
{
    public function index(NovaRequest $request)
    {
        $resource = new $request->resourceClass($request->resourceClass::newModel());
        $fields = collect($resource->fields($request));

        $field = $fields->first(function ($value, $key) use ($request) {
            return ($value instanceof NovaBelongsToDepend && $value->attribute == $request->attribute);
        });

        $model = $request->modelClass::find($request->dependKey);
        $result = ($field->optionResolveCallback)($model);

        return $result;
    }
}
