<?php

namespace Orlyapps\NovaBelongsToDepend\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Http\Resources\MergeValue;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Panel;
use Laravel\Nova\Resource;
use Orlyapps\NovaBelongsToDepend\NovaBelongsToDepend;

class FieldController extends Controller
{
    public function index(NovaRequest $request)
    {
        if (is_null($request->dependsMap)) {
            abort(500, 'Depend On Relationship not found on the Resource specified for the Field "' . $request->attribute . '" Please check you have set correct /App/Nova/Resource');
        }

        $resource = Nova::resourceInstanceForKey($request->resource);
        if (is_null($resource)) {
            abort(500, 'Could not find resource "' . $request->resource);
        }

        $resourceModel = null;
        if (!empty($request->resourceId)) {
            $resourceModel = $resource::newModel()->find($request->resourceId);
        }

        $attributedField = $this->getAttributedField($request, $resource);

        $modelMap = $this->getModelMap($request, $attributedField);

        if (empty($modelMap)) {
            return [];
        }

        $options = $this->getOptions($attributedField, $modelMap, $resourceModel);

        return $options instanceof Collection ? $options : [$options];
    }

    /**
     * @param array $fields
     * @return Collection
     */
    public function returnFields(array $fields)
    {
        return collect($fields)->map(function ($field) {
            if (isset($field->data)) {
                return $this->returnFields($field->data);
            }

            if (isset($field->meta['fields'])) {
                return $this->returnFields($field->meta['fields']);
            }

            if (isset($field->fields)) {
                return $this->returnFields($field->fields);
            }

            return $field;
        })->flatten();
    }

    /**
     * @param NovaRequest $request
     * @param Resource $resource
     * @return NovaBelongsToDepend
     */
    private function getAttributedField(NovaRequest $request, Resource $resource)
    {
        // Create Nested Array Fields from Panels, filter out irrelevant fields
        $fields = $this->returnFields($resource->fields($request));

        $fields = $fields->filter(function ($value) {
            return ($value instanceof NovaBelongsToDepend);
        });

        // get the attributed field
        $attributedField = $fields->first(function ($value, $key) use ($request) {
            return ($value instanceof NovaBelongsToDepend && $value->attribute == $request->attribute);
        });

        if (is_null($attributedField)) {
            abort(500, 'Can not find the Field "' . $request->attribute . '" in the Model "' . get_class($resource) . '"');
        }

        return $attributedField;
    }

    /**
     * @param NovaRequest $request
     * @param NovaBelongsToDepend $attributedField
     * @return array
     */
    private function getModelMap(NovaRequest $request, NovaBelongsToDepend $attributedField)
    {
        $models = [];

        foreach ($request->dependsMap as $value) {
            $modelClass = Nova::modelInstanceForKey($value['key'] ?? $request->modalClass);
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

    private function getOptions(NovaBelongsToDepend $attributedField, array $modelMap, $resourceModel = null)
    {
        $options = null;

        if (count($modelMap) == 1 && count($attributedField->dependsOn) == 1) {
            $model = $modelMap[$attributedField->dependsOn[0]];
            $options = ($attributedField->optionResolveCallback)($model, $resourceModel);
        } else {
            $options = ($attributedField->optionResolveCallback)((object)$modelMap, $resourceModel);
        }

        if (is_null($options)) {
            abort(500, 'Failed to create Result from ' . $attributedField->optionResolveCallback . ' with given Model');
        }

        return $options;
    }
}
