<template>
    <div class="box box-primary">
            <div class="box-header with-border">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="box-title">Dashboard</h4>
                    <div style="width: 200px; margin-right: 10px;">
                        <Select2Select 
                    :options="branches" 
                    optionValue="id" 
                    optionLabel="name" 
                    placeholder="Select Branch..." 
                    v-model="branchId" 
                      style="width: 350px;"
                />
                    
                    </div>

                </div>
            </div>
            <div class="box-body">
                    <div class="row">
                        <StatCard 
                            color="bg-aqua"
                            title="Total Debtor Balances"
                            :value="isLoading ? null : numberWithCommas(debtorBalances ?? 0)"
                            icon="fas fa-money-bill-wave"
                            route="/admin/maintain-customers"
                        />

                        <StatCard 
                            color="bg-red"
                            title="Previous Month Sales"
                            :value="isLoading ? null : numberWithCommas(monthlySales?.[0] ?? 0)"
                            icon="fas fa-money-bill-wave"
                        />

                        <StatCard 
                            color="bg-green"
                            title="Last Month Sales"
                            :value="isLoading ? null: numberWithCommas(monthlySales?.[1] ?? 0)"
                            icon="fas fa-money-bill-wave"
                        />

                        <StatCard 
                            color="bg-blue"
                            title="Current Month Sales"
                            :value="isLoading ? null : numberWithCommas(monthlySales?.[2] ?? 0)"
                            icon="fas fa-money-bill-wave"
                        />
                        
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <Card cardTitle="Sales vs Payments Performance">
                                <SalesVsPaymentsChart :branchId :salesData :returnsData :months />
                            </Card>
                        </div>

                        <div class="col-md-6">
                            <Card cardTitle="Tonnage Performance">
                                <TonnagePerformanceChart :months :tonnageData />
                            </Card>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <Card cardTitle="Returns">
                                <ReturnsChart :branchId :returnsData :months />
                            </Card>
                        </div>

                        <div class="col-md-6">
                            <Card cardTitle="Summarized Monthly Performance">
                                <SummarizedPerformanceChart :branchId :salesData :months  />
                            </Card>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <DepartmentalSalesPerformance :branchId :departmentalPerformanceData />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <DepartmentalTonnagePerformance :branchId :departmentalPerformanceData />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <Card cardTitle="Met Monthly Performance">
                                <MetPerformanceChart :branchId :months />
                            </Card> 
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <Card cardTitle="Branch Performance">
                                <template #header-action>
                                    <a :href="detailedBranchPerformanceRoute" target="_blank" class="btn btn-sm btn-primary">
                                        Detailed Branch Performance
                                        <i class="fa fa-arrow-circle-right"></i>
                                    </a>
                                </template>

                                <BranchPerformance />
                            </Card> 
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <Card cardTitle="Route Sales Performance">
                                <template #header-action>
                                    <a :href="detailedRouteSalesPerformanceRoute" target="_blank" class="btn btn-sm btn-primary">
                                        Detailed Route Sales Performance
                                        <i class="fa fa-arrow-circle-right"></i>
                                    </a>
                                </template>

                                <RouteSalesPerformance :branchId :months />
                            </Card>
                        </div>
                    </div>

            </div>


    </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import dayjs from 'dayjs'
import _ from 'lodash'
import Card from '@/components/ui/CardWithoutBorder.vue'
import StatCard from '@/components/ui/StatCard.vue'
import Select2Select from '@/components/ui/form/Select2Select.vue'
import SalesVsPaymentsChart from '@/components/chairman-dashboard/SalesVsPaymentsChart.vue'
import TonnagePerformanceChart from '@/components/chairman-dashboard/TonnagePerformanceChart.vue'
import ReturnsChart from '@/components/chairman-dashboard/ReturnsChart.vue'
import SummarizedPerformanceChart from '@/components/chairman-dashboard/SummarizedPerformanceChart.vue'
import MetPerformanceChart from '@/components/chairman-dashboard/MetPerformanceChart.vue'
import BranchPerformance from '@/components/chairman-dashboard/BranchPerformance.vue'
import RouteSalesPerformance from '@/components/chairman-dashboard/RouteSalesPerformance.vue'
import DepartmentalSalesPerformance from '@/components/chairman-dashboard/DepartmentalSalesPerformance.vue'
import DepartmentalTonnagePerformance from '@/components/chairman-dashboard/DepartmentalTonnagePerformance.vue'
import { numberWithCommas } from '@/utils.js'
import { useApi } from '@/composables/useApi.js'
import formUtil from '@/composables/useForm.js'

const { apiClient } = useApi()

const props = defineProps({
    branches: {
        required: true,
        type: String
    },
    branchId: {
        required: true,
        type: String
    },
    // monthlySales: {
    //     required: true,
    //     type: String
    // },
    // debtorBalances: {
    //     required: true,
    //     type: String
    // },
    pageRoute: {
        required: true,
        type: String
    },
    detailedBranchPerformanceRoute: {
        required: true,
        type: String
    },
    detailedRouteSalesPerformanceRoute: {
        required: true,
        type: String
    }
})

const branchId = ref(props.branchId)
const branches = JSON.parse(props.branches);
// const monthlySales = JSON.parse(props.monthlySales);
const monthlySales = ref([0, 0, 0])
const debtorBalances = ref(0) 
const isLoading = ref(true)


const months = []

const currentYear = dayjs().year()
const currentMonth = dayjs().month() + 1

for (let i = 1; i <= currentMonth; i++) {
  const monthName = dayjs(`${currentYear}-${i}-01`).format('MMMM')
  const monthKey = dayjs(`${currentYear}-${i}-01`).format('YYYY-MM')
  months[monthKey] = monthName
}

// Watch branchId for changes and trigger filtering
watch(branchId, (newBranchId) => {
    if (newBranchId) {
        handleFilter();
    }
});



const handleFilter = () => {
    window.location = `/${props.pageRoute}?branch_id=${branchId.value}`
}

const salesData = ref({});
const returnsData = ref({});
const tonnageData = ref({})
const departmentalPerformanceData = ref([])
const fetchData = async () => {
    isLoading.value = true;
    try {
        // Fetch debtor balances
        const balancesResponse = await apiClient.get(`chairman-dashboard-debtors/${branchId.value}`);
        debtorBalances.value = Number(balancesResponse.data);  
        
        // Fetch sales stats
        const salesResponse = await apiClient.get(`chairman-dashboard-sales-stats/${branchId.value}`);
        monthlySales.value = salesResponse.data.map(Number);  

    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchData()
    // Fetch sales data
    apiClient.get('chairman-dashboard-sales/' + branchId.value)
        .then(response => salesData.value = response.data)
        .catch(error => formUtil.errorMessage(error.response.data.message))

    // Fetch returns data
    apiClient.get('chairman-dashboard-returns/' + branchId.value)
        .then(response => returnsData.value = response.data)
        .catch(error => formUtil.errorMessage(error.response.data.message))

    // Fetch tonnage data
    apiClient.get(`chairman-dashboard-tonnage/${branchId.value}`)
        .then(response => tonnageData.value = _.reverse(response.data))
        .catch(error => formUtil.errorMessage(error.response.data.message))

    // Fetch departmental performance data
    apiClient.get('chairman-dashboard-category-performance/' + branchId.value, {
        params: {
            year: dayjs().year(),
            month: dayjs().format('MMMM')
        }
    })
        .then(response => departmentalPerformanceData.value = response.data)
        .catch(error => formUtil.errorMessage(error.response.data.message))
})

</script>
