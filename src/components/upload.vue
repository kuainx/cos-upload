<template>
<div>
	<div v-for="file in fileList" :key="file.name">
		<prog :file="file"></prog>
	</div>
	<a-button type="primary" @click="start" :loading="loading">
		开始上传
	</a-button>
</div>
</template>
<script>
import prog from './progress.vue'
export default {
	name: "upload",
	data: () => ({
		fileList: [],
		Bucket: 'life-1252428915',
		Region: 'ap-guangzhou',
		protocol: 'https:',
		loading: false
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
					console.log(that.fileArr[that.fileList[index].index]);
					xhr.send(that.fileArr[that.fileList[index].index]);
				})
			}
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
		}
	},
	watch: {
		fileList: {
			handler() {
				if (this.loading) {
					for (let file of this.fileList) {
						if (file.size != file.uploaded) {
							return;
						}
					}
					this.$parent.current = 4;
				}
			},
			deep: true
		}
	}
}
</script>
<style scoped>
</style>
