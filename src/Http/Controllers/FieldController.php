<?php

namespace Orlyapps\NovaBelongsToDepend\Http\Controllers;

use \Illuminate\Http\Resources\MergeValue;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Orlyapps\NovaBelongsToDepend\NovaBelongsToDepend;

class FieldController extends Controller
{
    public function index(NovaRequest $request)
    {
        if (is_null($request->dependsMap)) {
            abort(500, 'Depend On Relationship not found on the Resource specified for the Field "' . $request->attribute . '" Please check you have set correct /App/Nova/Resource');
        }

        if (method_exists($request->resourceClass, 'newModel')) {
            $resource = new $request->resourceClass($request->resourceClass::newModel());
        } else {
            $resource = new $request->resourceClass;
        }

        $attributedField = $this->getAttributedField($request, $resource);

        $modelMap = $this->getModelMap($request, $attributedField);

        if (empty($modelMap)) {
            return [];
        }

        $options = $this->getOptions($attributedField, $modelMap);

        return $options instanceof Collection ? $options : [$options];
    }

    public function returnFields($fields)
    {
        return collect($fields)->map(function ($field) {
            if (isset($field->data)) {
                return $this->returnFields($field->data);
            } elseif (isset($field->meta['fields'])) {
                return $this->returnFields($field->meta['fields']);
            } elseif (isset($field->fields)) {
                return $this->returnFields($field->fields);
            }

            return $field;
        })->flatten();
    }

    private function getAttributedField($request, $resource)
    {
        // Create Nested Array Fields from Panels, filter out irrelevant fields
        $fields = $this->returnFields($resource->fields($request));

        $fields = $fields->filter(
            function ($value) use ($request) {
                return ($value instanceof NovaBelongsToDepend);
            }
        );

        //get the attributed field
        $attributedField = $fields->first(function ($value, $key) use ($request) {
            return ($value instanceof NovaBelongsToDepend && $value->attribute == $request->attribute);
        });

        if (is_null($attributedField)) {
            abort(500, 'Can not find the Field "' . $request->attribute . '" in the Model "' . $request->resourceClass . '"');
        }

        return $attributedField;
    }

    private function getModelMap($request, $attributedField)
    {
        $models = [];

        foreach ($request->dependsMap as $value) {

            $modelClass = $value['key'] ?? $request->modalClass;
            if (is_null($modelClass::find($value['value']))) {
                abort(500, 'Can not find the Model "' . $modelClass . '::find(' . $value['value'] . ')');
            }

            array_push($models, $modelClass::find($value['value']));
        }

        $modelMap = [];

        if (count($attributedField->dependsOn) == count($models)) {
            for ($i = 0; $i < count($models); $i++) {
                $modelMap[$attributedField->dependsOn[$i]] = $models[$i];
            }
        }

        return $modelMap;
    }

    private function getOptions($attributedField, $modelMap)
    {
        $options = null;

        if (count($modelMap) == 1 && count($attributedField->dependsOn) == 1) {

            $model = $modelMap[$attributedField->dependsOn[0]];
            $options = ($attributedField->optionResolveCallback)($model);

        } else {
            $options = ($attributedField->optionResolveCallback)((object)$modelMap);
        }

        if (is_null($options)) {
            abort(500, 'Failed to create Result from ' . $attributedField->optionResolveCallback . ' with given Model');
        }

        return $options;
    }
}
