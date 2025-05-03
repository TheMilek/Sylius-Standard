import { startStimulusApp } from '@symfony/stimulus-bridge';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
  '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
  true,
  /\.[jt]sx?$/
));
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

// In this file you can import assets like images or stylesheets
console.log('Hello Webpack Encore! Edit me in assets/admin/entrypoint.js');
