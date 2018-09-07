<?php

namespace Orlyapps\NovaBelongsToDepend;

use Illuminate\Support\Str;
use Laravel\Nova\Fields\FormatsRelatableDisplayValues;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Laravel\Nova\Fields\BelongsTo;

class NovaBelongsToDepend extends BelongsTo
{
    use FormatsRelatableDisplayValues;

    public $resourceParentClass;

    public $modelClass;
    public $modelPrimaryKey;
    public $foreignKeyName;

    public $valueKey;

    public $titleKey;

    public $dependKey;
    public $dependsOn;

    public $optionResolveCallback = null;
    public $options = [];

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nova-belongsto-depend';

    public function __construct($name, $attribute = null, $resource = null)
    {
        $resource = $resource ?? ResourceRelationshipGuesser::guessResource($name);
        parent::__construct($name, $attribute, $resource);

        $this->modelClass = get_class($resource::newModel());
        $this->modelPrimaryKey = $resource::newModel()->getKeyName();
        $this->titleKey = Nova::resourceForKey(str_plural($this->attribute))::$title;
        $this->optionResolveCallback = function () {
            return [];
        };
    }

    public function options($options)
    {
        $this->options = collect($options);
        return $this;
    }

    public function optionsResolve($callback)
    {
        $this->optionResolveCallback = $callback;
        return $this;
    }

    public function dependsOn($relationship)
    {
        $this->dependsOn = Str::lower($relationship);
        return $this;
    }

    public function resolve($resource, $attribute = null)
    {
        parent::resolve($resource, $attribute);

        $this->resourceParentClass = get_class(Nova::newResourceFromModel($resource));

        $foreign = $resource->{$this->attribute}();
        $this->foreignKeyName = $foreign->getForeignKey();

        if ($this->dependsOn) {
            $this->dependKey = $resource->{$this->dependsOn}()->getForeignKey();
        }

        $value = $resource->{$this->attribute}()->withoutGlobalScopes()->first();
        if ($value) {
            $this->valueKey = $value->getKey();
            $this->value = $this->formatDisplayValue($value);
        }
    }

    public function meta()
    {
        $this->meta = parent::meta();
        return array_merge([
            'options' => $this->options,
            'valueKey' => $this->valueKey,
            'dependKey' => $this->dependKey,
            'dependsOn' => $this->dependsOn,
            'titleKey' => $this->titleKey,
            'resourceParentClass' => $this->resourceParentClass,
            'modelClass' => $this->modelClass,
            'modelPrimaryKey' => $this->modelPrimaryKey,
            'foreignKeyName' => $this->foreignKeyName,
        ], $this->meta);
    }
}
