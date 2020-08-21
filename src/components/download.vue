<template>
<div class="margin">
	<a-steps :current="current">
		<a-step title="用户">
			<a-icon slot="icon" type="solution" />
		</a-step>
		<a-step title="项目">
			<a-icon slot="icon" type="user" />
		</a-step>
		<a-step title="下载">
			<a-icon slot="icon" type="download" />
		</a-step>
	</a-steps>
	<div class="margin">
		<div v-if="current==0">
			<a-input v-model="phone" placeholder="请输入手机号">
				<a-icon slot="prefix" type="solution" />
				<a-tooltip slot="suffix" title="请输入从商城下单时填写的联系方式">
					<a-icon type="info-circle" style="color: rgba(0,0,0,.45)" />
				</a-tooltip>
			</a-input>
			<a-button class="margin" type="primary" @click="phonecheck" :disabled="phone.length==0" :loading="phoneloading">
				校验
				<a-icon type="right" />
			</a-button>
		</div>
		<div v-if="current==1">
			<div class="margin">
				<a-radio-group v-model="orderselect">
					<a-radio class="radio" v-for="(order,index) in orderList" :key="index" :value="index">
						订单{{order.phone}}-{{order.user}} - 备注：{{order.msg?order.msg:'/'}}
					</a-radio>
				</a-radio-group>
			</div>
			<div class="margin">
				<a-button-group>
					<a-button type="primary" @click="current=0">
						<a-icon type="left" />返回
					</a-button>
					<a-button type="primary" @click="projcheck" :loading="userloading" :disabled="orderselect<0">
						提交
						<a-icon type="right" />
					</a-button>
				</a-button-group>
			</div>
		</div>
		<div v-if="current==2">
			<div class="margin">
				<a-button type="primary" @click="download">
					点击下载文件 {{orderList[orderselect]['file']}}
				</a-button>
				<span class="ant-btn ant-btn-link" v-clipboard:copy="link" v-clipboard:success="onCopy" v-clipboard:error="onError">点击复制链接</span>
			</div>
			<a-button type="primary" @click="download1">
				无法下载请点击
			</a-button>

			<div class="margin">
				<a-button-group>
					<a-button type="primary" @click="current=1">
						<a-icon type="left" />返回
					</a-button>
				</a-button-group>
			</div>
		</div>
	</div>
</div>
</template>
<script>
import $ from 'jquery'
export default {
	name: "download",
	data: () => ({
		current: 0,
		phoneloading: false,
		phone: '',
		orderList: [],
		orderselect: -1,
	}),
	methods: {
		phonecheck() {
			this.phoneloading = true;
			$.ajax({
				type: "post",
				url: "//faka.lifestudio.cn/up/api/api.php?t=getproj",
				data: {
					phone: this.phone
				},
				dataType: 'json',
				success: (data) => {
					console.log(data);
					if (data.status == 0) {
						this.orderList = data.ret;
						this.current = 1;
					} else {
						this.$error({
							title: '校验失败',
							content: '错误码：' + data.status + '，错误信息：' + data.ret,
						});
					}
					this.phoneloading = false;
				},
				error: (xhr, err) => {
					console.log(xhr, err);
					this.$error({
						title: '校验失败',
						content: '网络错误：' + err,
					});
					this.phoneloading = false;
				}
			});
		},
		projcheck() {
			if (this.orderList[this.orderselect]['file'] == '') {
				this.$warning({
					title: '提示',
					content: '该项目暂未完成，请耐心等待',
				});
			} else {
				this.current = 2;
			}
		},
		download() {
			let a = document.createElement('a');
			a.href = this.link;
			a.click();
		},
		onCopy() {
			this.$message.success("链接已复制到剪切板！");
		},
		onError() {
			this.$message.error("抱歉，链接复制失败！");
		},
		download1() {
			window.open(this.link);
		}
	},
	computed: {
		link() {
			if (this.orderList[this.orderselect]) {
				let b = this.orderList[this.orderselect];
				return "https://life-down-1252428915.cos.ap-guangzhou.myqcloud.com/" + b['phone'] + '-' + b['user'] + '/' + b['file'];
			} else {
				return '';
			}
		}
	}
}
</script>
<style scoped>
.margin {
	margin: 10px;
}

.radio {
	display: block;
	height: 30px;
	line-height: 30px;
}
</style>
