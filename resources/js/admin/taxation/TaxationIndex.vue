<template>
    <div class="container-fluid " style="padding: 16px 18px;">
        <TaxationHeader/>
        <TaxationCard/>
        <TaxationBody/>
        <TaxSettings/>
    </div>
</template>

<script>
import TaxationHeader from './parts/TaxationHeader.vue';
import TaxationCard from './parts/TaxationCard.vue';
import TaxationBody from './parts/TaxationBody.vue';
import TaxSettings from './parts/TaxSettings.vue';
export default {
    components: {
        TaxationHeader,
        TaxationCard,
        TaxationBody,
        TaxSettings
    }
}
</script>