<template>
<div id="container">
	<div id="app">
		<img alt="logo" src="./assets/logo.png" class="logo">
		<steps></steps>

	</div>
</div>
</template>

<script>
import steps from './components/steps.vue'

export default {
	name: 'App',
	components: {
		steps
	},
	data: () => ({

	}),
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
