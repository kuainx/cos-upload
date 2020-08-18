<template>
<div id="container">
	<div id="app">
		<img alt="logo" src="./assets/logo.png" class="logo">
		<router-view></router-view>
	</div>
</div>
</template>

<script>
export default {
	name: 'App'
}
</script>

<style scoped>
#container {
	display: table;
	height: 100%;
	margin: auto;
	text-align: center;
}

#app {
	display: table-cell;
	vertical-align: middle;
}

.logo {
	width: 300px;
}
</style>
