<template>
    <data-list :columns="columns" :rows="rows">
        <div class="card p-0" slot-scope="{ filteredRows: rows }">
            <data-list-table :rows="rows">
                <template slot="cell-title" slot-scope="{ row: collection }">
                    <a :href="collection.edit_url">{{ collection.title }}</a>
                </template>
                <template slot="actions" slot-scope="{ row: collection, index }">
                    <dropdown-list>
                        <dropdown-item :text="__('Edit')" :redirect="collection.edit_url" />
                        <dropdown-item
                            v-if="collection.deleteable"
                            :text="__('Delete')"
                            class="warning"
                            @click="confirmDeleteRow(collection.slug, index)" />
                    </dropdown-list>

                    <!-- TODO: A really nice toast notification would be great, if the product cant be deleted -->

                    <confirmation-modal
                        v-if="deletingRow !== false"
                        :title="deletingModalTitle"
                        :bodyText="__('Are you sure you want to delete this tax? You will not be able to delete this shipping if used by any product.')"
                        :buttonText="__('Delete')"
                        :danger="true"
                        @confirm="deleteRow('/butik/settings/taxes')"
                        @cancel="cancelDeleteRow"
                    >
                    </confirmation-modal>
                </template>
            </data-list-table>
        </div>
    </data-list>
</template>

<script>
    import DeletesListingRow from '../DeletesListingRow.js'

    export default {

        mixins: [DeletesListingRow],

        props: [
            'initial-rows',
            'columns',
        ],

        data() {
            return {
                rows: this.initialRows
            }
        }

    }
</script>
