<template>
<a-upload-dragger class="drag-area" name="file" :multiple="true" @change="handleChange" :remove="handleRemove" :before-upload="beforeUpload">
	<p class="ant-upload-drag-icon">
		<a-icon type="inbox" />
	</p>
	<p class="ant-upload-text">
		单击或拖拽文件至本区域以选择文件
	</p>
	<p class="ant-upload-hint">
		支持单文件/多文件选择，单个文件不得超过5GB
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
