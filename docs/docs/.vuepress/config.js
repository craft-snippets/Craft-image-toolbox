module.exports = {

  head: [
    ['link', { rel: 'icon', href: 'http://craftsnippets.com/static/fav.png' }]
  ],

    title: 'Image toolbox Documentation',
    description: 'Documentation for the Image toolbox Craft CMS plugin',
    base: '/docs/image-toolbox/',
    themeConfig: {
        displayAllHeaders: true,
        sidebar: [
            ['/', 'Introduction'],
            ['/Picture', 'Outputting images'],
            ['/Placeholders', 'Placeholders'],
            ['/Layouts', 'Transform layouts'],
            ['/Methods', 'Methods list'],
            ['/Settings', 'Settings'],
        ],
        nav: [
          { text: 'craftsnippets.com', link: 'http://craftsnippets.com/' }
        ],
      
    }
};
