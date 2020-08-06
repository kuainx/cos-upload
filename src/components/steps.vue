<template>
<div class="steps">
	<a-steps :current="current">
		<a-step title="Verification">
			<a-icon slot="icon" type="solution" />
		</a-step>
		<a-step title="Login">
			<a-icon slot="icon" type="user" />
		</a-step>
		<a-step title="Select">
			<a-icon slot="icon" type="file-search" />
		</a-step>
		<a-step title="Upload">
			<a-icon slot="icon" type="upload" />
		</a-step>
	</a-steps>
	<div class="steps-content">
		<div v-if="current==0">
			<a-form-item has-feedback :validate-status="carderror" :help="cardhelp" :loading="cardloading">
				<a-input v-model="card" placeholder="请输入激活码">
					<a-icon slot="prefix" type="solution" />
					<a-tooltip slot="suffix" title="请输入从商城购买的激活码">
						<a-icon type="info-circle" style="color: rgba(0,0,0,.45)" />
					</a-tooltip>
				</a-input>
			</a-form-item>
			<a-button type="primary" @click="cardcheck" :disabled="carddisable" :loading="cardloading">
				校验
				<a-icon type="right" />
			</a-button>
		</div>
		<div v-if="current==1">
			<a-input v-model="user" placeholder="请输入项目名">
				<a-icon slot="prefix" type="user" />
				<a-tooltip slot="suffix" title="新激活码输入项目自动注册，已用激活码输入设定项目名进入">
					<a-icon type="info-circle" style="color: rgba(0,0,0,.45)" />
				</a-tooltip>
			</a-input>
			<div class="control">
				<a-button-group>
					<a-button type="primary" @click="current=0">
						<a-icon type="left" />返回
					</a-button>
					<a-button type="primary" @click="usercheck" :loading="userloading" :disabled="user.length==0">
						提交
						<a-icon type="right" />
					</a-button>
				</a-button-group>
			</div>

		</div>
		<div v-if="current==2">
			<selector v-model="fileList"></selector>
			<div class="control">
				<a-button-group>
					<a-button type="primary" @click="current=1">
						<a-icon type="left" />返回
					</a-button>
					<a-button type="primary" @click="uploadcheck" :loading="uploadloading" :disabled="fileList.length==0">
						开始上传
						<a-icon type="right" />
					</a-button>
				</a-button-group>
			</div>
		</div>
		<div v-if="current==3">
			<upload :fileStatus="fileStatus" :auth="auth" :user="user" :fileArr="fileList"></upload>
		</div>
	</div>
	<div class="steps-action">
		<a-button type="primary" @click="next">
			Next
		</a-button>
		<a-button style="margin-left: 8px" @click="prev">
			Previous
		</a-button>
	</div>
</div>
</template>
<script>
import selector from './selector.vue'
import upload from './upload.vue'
import $ from 'jquery'
export default {
	name: "steps",
	data: () => ({
		current: 0,
		carderror: '',
		cardhelp: '',
		cardloading: false,
		carddisable: false,
		card: '',
		userloading: false,
		user: '',
		uploadloading: false,
		fileList: [],
		fileStatus: {},
		auth: {},
		onupload: 0
	}),
	components: {
		selector,
		upload
	},
	methods: {
		next() {
			this.current++;
		},
		prev() {
			this.current--;
		},
		cardcheck() {
			this.cardloading = true;
			$.ajax({
				type: "post",
				url: "//lifestudio.cn/up/api/api.php?t=cardvalid",
				data: {
					card: this.card
				},
				dataType: 'json',
				success: (data) => {
					console.log(data);
					if (data.status == 0) {
						this.current = 1;
					} else {
						this.$error({
							title: '校验失败',
							content: '错误码：' + data.status + '，错误信息：' + data.ret,
						});
					}
					this.cardloading = false;
				},
				error: (xhr, err) => {
					console.log(xhr, err);
					this.$error({
						title: '校验失败',
						content: '网络错误：' + err,
					});
					this.cardloading = false;
				}
			});
		},
		usercheck() {
			this.userloading = true;
			$.ajax({
				type: "post",
				url: "//lifestudio.cn/up/api/api.php?t=uservaild",
				data: {
					card: this.card,
					user: this.user
				},
				dataType: 'json',
				success: (data) => {
					console.log(data);
					if (data.status == 0) {
						this.current = 2;
					} else {
						this.$error({
							title: '提交失败',
							content: '错误码：' + data.status + '，错误信息：' + data.ret,
						});
					}
					this.cardloading = false;
				},
				error: (xhr, err) => {
					console.log(xhr, err);
					this.$error({
						title: '提交失败',
						content: '网络错误：' + err,
					});
					this.cardloading = false;
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
						url: "//lifestudio.cn/up/api/api.php?t=getauth",
						data: {
							card: that.card,
							user: that.user
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
		},
		uploadFile() {}
	},
	watch: {
		card() {
			if (this.card.length == 8) {
				this.carderror = 'success';
				this.cardhelp = '';
				this.carddisable = false;
			} else if (this.card.length == 0) {
				this.carderror = '';
				this.cardhelp = '';
				this.carddisable = true;
			} else {
				this.carderror = 'error';
				this.cardhelp = '激活码为8位';
				this.carddisable = true;
			}
		}
	}
}
</script>
<style scoped>
.steps {
	margin: 10px;
}

.steps-content {
	margin: 10px;
}

.control {
	margin: 10px;
}
</style>
<style>
.ant-form-item-children-icon i svg {
	margin-left: 40px !important;
}
</style>
