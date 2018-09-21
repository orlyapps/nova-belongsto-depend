<template>
    <default-field :field="field">
        <template slot="field">
            <multiselect
                v-model="value"
                :options="options"
                :placeholder="this.field.indexName + ' ' +__('Select')"
                :selectLabel="__('Press enter to select')"
                :selectedLabel="__('Selected')"
                :deselectLabel="__('Press enter to remove')"
                :custom-label="customLabel"
                @input="onChange">
                <span slot="noResult">
                    {{ __('Oops! No elements found. Consider changing the search query.')}}
                </span>
            </multiselect>
            <p v-if="hasError" class="my-2 text-danger">
                {{ firstError }}
            </p>
        </template>
    </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors } from "laravel-nova";
import Multiselect from "vue-multiselect";

export default {
    components: { Multiselect },
    mixins: [FormField, HandlesValidationErrors],

    props: ["resourceName", "resourceId", "field"],

    data() {
        return {
            options: []
        };
    },
    created() {
        if (this.field.dependsOn) {
            Nova.$on("nova-belongsto-depend-" + this.field.dependsOn, async dependsOnValue => {
                this.value = "";

                Nova.$emit("nova-belongsto-depend-" + this.field.attribute.toLowerCase(), {
                    value: this.value,
                    field: this.field
                });

                if (dependsOnValue && dependsOnValue.value) {
                    this.options = (await Nova.request().post("/nova-vendor/nova-belongsto-depend", {
                        resourceClass: this.field.resourceParentClass,
                        modelClass: dependsOnValue.field.modelClass,
                        attribute: this.field.attribute,
                        dependKey: dependsOnValue.value[dependsOnValue.field.modelPrimaryKey]
                    })).data;

                    if (this.field.valueKey) {
                        this.value = this.options.find(item => item[this.field.modelPrimaryKey] == this.field.valueKey);
                        Nova.$emit("nova-belongsto-depend-" + this.field.attribute.toLowerCase(), {
                            value: this.value,
                            field: this.field
                        });
                    }
                }
            });
        }
    },

    methods: {
        customLabel(item) {
            return item[this.field.titleKey];
        },
        /*
         * Set the initial, internal value for the field.
         */
        setInitialValue() {
            this.options = this.field.options;
            if (this.field.value) {
                this.value = this.options.find(item => item[this.field.modelPrimaryKey] == this.field.valueKey);
                if (this.value) {
                    Nova.$emit("nova-belongsto-depend-" + this.field.attribute.toLowerCase(), {
                        value: this.value,
                        field: this.field
                    });
                }
            }
        },

        /**
         * Fill the given FormData object with the field's internal value.
         */
        fill(formData) {
            if (this.value) {
                formData.append(this.field.attribute, this.value[this.field.modelPrimaryKey] || "");
            }
        },

        /**
         * Update the field's internal value.
         */
        handleChange(value) {
            this.value = value;
        },

        async onChange(value) {
            Nova.$emit("nova-belongsto-depend-" + this.field.attribute.toLowerCase(), {
                value,
                field: this.field
            });
        }
    }
};
</script>

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<style>
.multiselect {
    box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.05);
    min-height: 36px !important;
    border-radius: 0.5rem;
}

.multiselect__tags {
    min-height: 36px !important;
    border: 1px solid var(--60) !important;
    color: var(--80);
    border-radius: 0.5rem !important;
}

.multiselect__select {
    background-repeat: no-repeat;
    background-size: 10px 6px;
    background-position: center right 0.75rem;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 6"><path fill="#35393C" fill-rule="nonzero" d="M8.293.293a1 1 0 0 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4A1 1 0 0 1 1.707.293L5 3.586 8.293.293z"/></svg>');
}

.multiselect__select:before {
    content: none !important;
}
</style>
