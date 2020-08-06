<template>
<a-upload-dragger class="drag-area" name="file" :multiple="true" @change="handleChange" :remove="handleRemove" :before-upload="beforeUpload">
	<p class="ant-upload-drag-icon">
		<a-icon type="inbox" />
	</p>
	<p class="ant-upload-text">
		Click or drag file to this area to upload
	</p>
	<p class="ant-upload-hint">
		Support for a single or bulk upload. Strictly prohibit from uploading company data or other band files
	</p>
</a-upload-dragger>
</template>
<script>
export default {
	name: "selector",
	model: {
		prop: 'fileList',
		event: 'change'
	},
	props: ['fileList'],
	data: () => ({
		thisFileList: []
	}),
	methods: {
		handleRemove(file) {
			let index = this.thisFileList.indexOf(file);
			let newFileList = this.thisFileList.slice();
			newFileList.splice(index, 1);
			this.thisFileList = newFileList;
		},
		beforeUpload(file) {
			console.log(file)
			this.thisFileList = [...this.thisFileList, file];
			return false;
		},
		handleChange(info) {
			// window.fileArr = info.fileList;
			console.log(info)
			// this.$emit('change', info.fileList);
		}
	},
	watch: {
		thisFileList() {
			this.$emit('change', this.thisFileList);
		}
	}
};
</script>
<style>
.ant-upload-drag {
	max-width: 500px;
	max-height: 200px;
}
</style>
