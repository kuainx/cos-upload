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
					<a-button type="primary" @click="projcheck" :loading="userloading" :disabled="user.length==0">
						提交
						<a-icon type="right" />
					</a-button>
				</a-button-group>
			</div>
		</div>
		<div v-if="current==2">

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
		orderselect: 0,
		projid: 0,
		userloading: false,
		user: '',
		msg: '',
		uploadloading: false,
		fileList: [],
		fileStatus: {},
		auth: {}
	}),
	methods: {
		phonecheck() {
			this.phoneloading = true;
			$.ajax({
				type: "post",
				url: "//faka.lifestudio.cn/up/api/api.php?t=phonevalid",
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
			if (this.orderselect == 0) {
				this.$error({
					title: '提交失败',
					content: '错误信息：请选择订单',
				});
			} else if (this.orderselect == -1) {
				this.$error({
					title: '提交失败',
					content: '错误信息：该功能暂未开放',
				});
			} else {
				this.newproj();
			}
		},
		newproj() {
			this.userloading = true;
			$.ajax({
				type: "post",
				url: "//faka.lifestudio.cn/up/api/api.php?t=newproj",
				data: {
					phone: this.phone,
					id: this.orderselect,
					proj: this.user,
					msg: this.msg == '' ? '/' : this.msg
				},
				dataType: 'json',
				success: (data) => {
					console.log(data);
					if (data.status == 0) {
						this.projid = data.ret;
						this.current = 2;
					} else {
						this.$error({
							title: '提交失败',
							content: '错误码：' + data.status + '，错误信息：' + data.ret,
						});
					}
					this.userloading = false;
				},
				error: (xhr, err) => {
					console.log(xhr, err);
					this.$error({
						title: '提交失败',
						content: '网络错误：' + err,
					});
					this.userloading = false;
				}
			});
			this.userloading = false;
		},
		uploadcheck() {
			this.uploadloading = true;
			let filename = [];
			for (let file of this.fileList) {
				if (filename.indexOf(file.name) != -1) {
					this.$error({
						title: '提交失败',
						content: '错误信息：文件池中不得有重名文件'
					});
					this.uploadloading = false;
					return;
				} else {
					filename.push(file.name);
				}
			}
			let that = this;
			this.$confirm({
				title: '是否确认上传？',
				content: '上传后无法取消或修改',
				onOk() {
					$.ajax({
						type: "post",
						url: "//faka.lifestudio.cn/up/api/api.php?t=getauth",
						data: {
							phone: that.phone,
							id: that.projid,
							proj: that.user
						},
						dataType: 'json',
						success: (data) => {
							console.log(data);
							if (data.status == 0) {
								if (!data.ret.credentials) {
									that.$error({
										title: '提交失败',
										content: '错误信息：服务端错误',
									});
								} else {
									that.auth = data.ret;
									that.fileStatus = [];
									for (let index in that.fileList) {
										that.fileStatus.push({
											name: that.fileList[index].name,
											size: that.fileList[index].size,
											index: index,
											uploaded: 0
										})
									}
									that.current = 3;
								}
							} else {
								that.$error({
									title: '提交失败',
									content: '错误码：' + data.status + '，错误信息：' + data.ret,
								});
							}
							that.uploadloading = false;
						},
						error: (xhr, err) => {
							console.log(xhr, err);
							that.$error({
								title: '提交失败',
								content: '网络错误：' + err,
							});
							that.uploadloading = false;
						}
					});
				},
				onCancel() {
					that.uploadloading = false;
				},
				cancelText: '否',
				okTest: '是'
			});
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
