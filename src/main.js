import Vue from 'vue'
import VueRouter from 'vue-router'
Vue.use(VueRouter);
import Antd from 'ant-design-vue';
import 'ant-design-vue/dist/antd.css';
Vue.use(Antd);

import App from './App.vue'
import Steps from './components/steps.vue'
import Download from './components/download.vue'

Vue.config.productionTip = false;
const routes = [{
        path: '/',
        component: Steps
    },
    {
        path: '/download',
        component: Download
    }
]

// 3. 创建 router 实例，然后传 `routes` 配置
// 你还可以传别的配置参数, 不过先这么简单着吧。
const router = new VueRouter({
    routes: routes
})

new Vue({
    render: h => h(App),
    router: router
}).$mount('#app')
