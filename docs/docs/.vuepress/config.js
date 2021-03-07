module.exports = {

  head: [
    ['link', { rel: 'icon', href: 'http://craftsnippets.com/static/fav.png' }]
  ],

    title: 'Image toolbox Documentation',
    description: 'Documentation for the Image toolbox Craft CMS plugin',
    // base: '/_pog/vuepress_images/docs/.vuepress/dist/',
    base: '/docs/image-toolbox/',
    themeConfig: {
        displayAllHeaders: true,
        sidebar: [
            ['/', 'Introduction'],
            ['/Basic', 'Basic Usage'],
            ['/Methods', 'Methods list'],
            ['/Settings', 'Settings'],
        ],
        nav: [
          { text: 'craftsnippets.com', link: 'http://craftsnippets.com/' }
        ],



        // codeLanguages: {
        //   php: "PHP",
        //   twig: "Twig",
        // },                
    }
};
