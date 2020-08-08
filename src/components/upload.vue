<template>
<div>
	<a-button v-show="loading" type="primary" @click="wechat=true" :disabled="wechatconfirm">
		{{detail}}
	</a-button>
	<div v-for="file in fileList" :key="file.name">
		<prog :file="file"></prog>
	</div>
	<a-button type="primary" @click="start" :loading="loading">
		开始上传
	</a-button>
	<a-modal title="微信绑定" :visible="wechat" @ok="handleOk" @cancel="wechat=false" okText="确认（无法再次修改）" cancelText="关闭">
		<p class="center">扫描二维码绑定微信或修改绑定</p>
		<p class="center"><canvas id="qrcode"></canvas></p>
		<p v-show="wechatnick.length!=0" class="center">已绑定微信账号 {{wechatnick}}</p>
	</a-modal>
</div>
</template>
<script>
import prog from './progress.vue'
import $ from 'jquery'
import QRCode from 'qrcode'
export default {
	name: "upload",
	data: () => ({
		fileList: [],
		Bucket: 'life-1252428915',
		Region: 'ap-guangzhou',
		protocol: 'https:',
		loading: false,
		wechat: false,
		wechatnick: '',
		wechatconfirm: false,
		detail: '请绑定微信',
		finishedajax: false
	}),
	props: ['fileStatus', 'auth', 'user', 'phone', 'fileArr'],
	components: {
		prog
	},
	mounted() {
		this.fileList = this.fileStatus;
	},
	computed: {
		prefix() {
			return this.protocol + '//' + this.Bucket + '.cos.' + this.Region + '.myqcloud.com/' + this.phone + '-' + this.user + '/'
		}
	},
	methods: {
		start() {
			this.loading = true;
			let that = this;
			for (let index in this.fileStatus) {
				let file = this.fileList[index];
				let Key = file.name; // 这里指定上传目录和文件名
				this.getAuthorization({
					Method: 'PUT',
					Pathname: '/' + this.phone + '-' + this.user + '/' + Key
				}, function (info) {
					let auth = info.Authorization;
					let XCosSecurityToken = info.XCosSecurityToken;
					let url = that.prefix + that.camSafeUrlEncode(Key).replace(/%2F/, '/');
					let xhr = new XMLHttpRequest();
					xhr.open('PUT', url, true);
					xhr.setRequestHeader('Authorization', auth);
					XCosSecurityToken && xhr.setRequestHeader('x-cos-security-token', XCosSecurityToken);
					xhr.upload.onprogress = function (e) {
						that.fileList[index].uploaded = e.loaded;
					};
					xhr.onload = function () {
						if (xhr.status === 200 || xhr.status === 206) {
							that.$message.success('文件 ' + Key + ' 上传成功')
						} else {
							that.$message.error('文件 ' + Key + ' 上传失败，状态码：' + xhr.status);
						}
					};
					xhr.onerror = function () {
						that.$message.error('文件 ' + Key + ' 上传失败，请检查是否没配置 CORS 跨域规则');
					};
					xhr.send(that.fileArr[that.fileList[index].index]);
				})
			}
			this.wechat = true;
			this.renderQRCode();
			this.checkWechat();
			this.checkNext();
		},
		renderQRCode() {
			if (document.getElementById('qrcode')) {
				QRCode.toCanvas(document.getElementById('qrcode'),
					'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx61b12b15f8cbb912&redirect_uri=https%3a%2f%2ffaka.lifestudio.cn%2fup%2fapi%2fserver.php&response_type=code&scope=snsapi_userinfo&state=' + this.phone +
					'#wechat_redirect',
					function (error) {
						if (error) console.error(error)
						console.log('qrcode success!');
					})
			} else {
				setTimeout(this.renderQRCode, 100);
			}
		},
		checkWechat() {
			$.ajax({
				type: "post",
				url: "//faka.lifestudio.cn/up/api/api.php?t=getwechat",
				data: {
					phone: this.phone
				},
				dataType: 'json',
				success: (data) => {
					console.log(data);
					if (data.status == 0) {
						this.wechatnick = data.ret
					} else {
						this.wechatnick = '';
					}
					if (this.wechatconfirm == false) {
						setTimeout(this.checkWechat, 1000);
					}
				},
				error: (xhr, err) => {
					console.log(xhr, err);
					this.$error({
						title: '校验失败',
						content: '网络错误：' + err,
					});
				}
			});
		},
		handleOk() {
			if (this.wechatnick != "") {
				this.wechatconfirm = true;
			} else {
				this.$error({
					title: '绑定失败',
					content: '还没有绑定微信账号',
				});
			}
			this.wechat = false;
		},
		getAuthorization(options, callback) {
			callback({
				XCosSecurityToken: this.auth.credentials.sessionToken,
				Authorization: window.CosAuth({
					SecretId: this.auth.credentials.tmpSecretId,
					SecretKey: this.auth.credentials.tmpSecretKey,
					Method: options.Method,
					Pathname: options.Pathname,
				})
			});
		},
		camSafeUrlEncode(str) {
			return encodeURIComponent(str)
				.replace(/!/g, '%21')
				.replace(/'/g, '%27')
				.replace(/\(/g, '%28')
				.replace(/\)/g, '%29')
				.replace(/\*/g, '%2A');
		},
		checkNext() {
			if (this.$parent.current == 3) {
				setTimeout(this.checkNext, 1000);
			}
			if (this.loading && this.wechatconfirm == true) {
				for (let file of this.fileList) {
					if (file.size != file.uploaded) {
						return;
					}
				}
				if (!this.finishedajax) {
					this.finishedajax = true;
					$.ajax({
						type: "post",
						url: "//faka.lifestudio.cn/up/api/api.php?t=finish",
						data: {
							phone: this.phone,
							wechat: this.wechatnick,
							proj: this.user
						},
						dataType: 'json',
						success: (data) => {
							console.log(data);
						},
						error: (xhr, err) => {
							console.log(xhr, err);
							this.$error({
								title: '校验失败',
								content: '网络错误：' + err,
							});
						}
					});
				}
				this.$parent.current = 4;
			}
		}
	},
	watch: {
		wechatnick() {
			if (this.wechatnick == "") {
				this.detail = "请绑定微信"
			} else {
				this.detail = "已绑定微信账号 " + this.wechatnick
			}
		}
	}
}
</script>
<style scoped>
.center {
	text-align: center;
}
</style>
