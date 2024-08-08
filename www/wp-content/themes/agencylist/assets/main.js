window.addEventListener('load', () => {
  loaded();
  loadWizard();
});

(function () {
  console.log('This gets invoked immediately. Lol');
})();

const loaded = () => {
  console.log('Radley is loading things!');
}

const loadWizard = () => {
  let args = {
    "wz_class": ".wizard",
    "wz_nav_style": "dots",
    "wz_button_style": ".btn .btn-sm .mx-3",
    "wz_ori": "vertical",
    "buttons": true,
    "navigation": 'buttons',
    "finish": "Save!"
  };

  const wizard = new Wizard(args);

  wizard.init();
}