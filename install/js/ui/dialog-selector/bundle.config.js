module.exports = {
	input: 'src/index.js',
	output: {
		js: 'dist/dialog-selector.bundle.js',
		css: 'dist/dialog-selector.bundle.css',
	},
	namespace: 'MB.UI.DialogSelector',
	browserslist: true,
	transformClasses: true,
};
