Nova.booting((Vue, router) => {
    Vue.component('index-nova-belongsto-depend', require('./components/IndexField'));
    Vue.component('detail-nova-belongsto-depend', require('./components/DetailField'));
    Vue.component('form-nova-belongsto-depend', require('./components/FormField'));
})
