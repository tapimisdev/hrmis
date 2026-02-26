<template>
    <div class="container-fluid " style="padding: 16px 18px;">
        <TaxationHeader @taxation-data-updated="fetchTaxation" />
        <TaxationCard
            :cards="taxationData.cards"    
        />
        <TaxationBody
            :body="taxationData.body"
        />
        <TaxSettings
            :settings="taxationData.settings"
        />
    </div>
</template>

<script>
import axios from "axios";

import TaxationHeader from './parts/TaxationHeader.vue';
import TaxationCard from './parts/TaxationCard.vue';
import TaxationBody from './parts/TaxationBody.vue';
import TaxSettings from './parts/TaxSettings.vue';

import { TaxationSettingModel } from "./TaxationModel";

import TaxationSkeleton from './components/TaxationSkeleton.vue';
export default {
    components: {
        TaxationHeader,
        TaxationCard,
        TaxationBody,
        TaxSettings,
        TaxationSkeleton
    },

    data(){
        return {
            taxationData: TaxationSettingModel(),
        }
    },

    methods: {
        fetchTaxation($event) {
            axios
                .get("/admin/taxation", {
                    params: { year: $event },
                })
                .then((response) => {
                    const data = response.data || {}
                    this.taxationData = TaxationSettingModel(data);

                    console.log("Fetched taxation data:", this.taxationData);
                })
                .catch((error) => {
                    console.error("Error fetching taxation data:", error);
                });
        },
    }
}
</script>