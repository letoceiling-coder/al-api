// AL API Gateway - Navigation & Language Switcher
// Version: 1.0.0

// Navigation configuration
const navigation = {
  ru: [
    { name: 'Главная', url: '/', icon: '🏠' },
    { name: 'Streaming', url: '/streaming-guide.html', icon: '📡' },
    { name: 'Загрузка файлов', url: '/multipart-guide.html', icon: '📎' },
    { name: 'Параметры', url: '/model-parameters-guide.html', icon: '⚙️' },
    { name: 'Ошибки', url: '/errors.html', icon: '⚠️' },
    { name: 'Swagger', url: '/api/documentation', icon: '📘' },
  ],
  en: [
    { name: 'Home', url: '/', icon: '🏠' },
    { name: 'Streaming', url: '/streaming-guide.html', icon: '📡' },
    { name: 'File Upload', url: '/multipart-guide.html', icon: '📎' },
    { name: 'Parameters', url: '/model-parameters-guide.html', icon: '⚙️' },
    { name: 'Errors', url: '/errors.html', icon: '⚠️' },
    { name: 'Swagger', url: '/api/documentation', icon: '📘' },
  ]
};

// Language switcher
class LanguageSwitcher {
  constructor() {
    this.currentLang = localStorage.getItem('al-api-lang') || 'ru';
    this.translations = {};
  }

  init() {
    this.loadTranslations();
    this.injectNavigation();
    this.injectLanguageSwitcher();
    this.applyLanguage(this.currentLang);
  }

  loadTranslations() {
    // Translations will be loaded from data-i18n attributes
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (!this.translations[key]) {
        this.translations[key] = {
          ru: el.getAttribute('data-i18n-ru') || el.textContent,
          en: el.getAttribute('data-i18n-en') || el.textContent
        };
      }
    });
  }

  injectNavigation() {
    const nav = document.createElement('nav');
    nav.id = 'al-api-nav';
    nav.style.cssText = `
      position: sticky;
      top: 0;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 1rem 2rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      z-index: 1000;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    `;

    const logo = document.createElement('div');
    logo.style.cssText = 'color: white; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 0.5rem;';
    logo.innerHTML = '<span>🤖</span><span>AL API Gateway</span>';

    const links = document.createElement('div');
    links.style.cssText = 'display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center;';

    const currentPath = window.location.pathname;
    navigation[this.currentLang].forEach(item => {
      const link = document.createElement('a');
      link.href = item.url;
      link.innerHTML = `${item.icon} <span>${item.name}</span>`;
      link.style.cssText = `
        color: white;
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
      `;

      // Highlight active page
      if (currentPath === item.url || (item.url === '/' && currentPath === '/index_docs.html')) {
        link.style.background = 'rgba(255,255,255,0.2)';
        link.style.fontWeight = 'bold';
      }

      link.addEventListener('mouseover', () => {
        if (currentPath !== item.url) {
          link.style.background = 'rgba(255,255,255,0.15)';
        }
      });

      link.addEventListener('mouseout', () => {
        if (currentPath !== item.url) {
          link.style.background = 'transparent';
        }
      });

      links.appendChild(link);
    });

    nav.appendChild(logo);
    nav.appendChild(links);

    // Insert navigation at the top of body
    if (document.body.firstChild) {
      document.body.insertBefore(nav, document.body.firstChild);
    } else {
      document.body.appendChild(nav);
    }
  }

  injectLanguageSwitcher() {
    const switcher = document.createElement('div');
    switcher.id = 'lang-switcher';
    switcher.style.cssText = `
      position: fixed;
      top: 80px;
      right: 20px;
      background: white;
      border-radius: 25px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.15);
      padding: 0.5rem;
      display: flex;
      gap: 0.5rem;
      z-index: 999;
    `;

    ['ru', 'en'].forEach(lang => {
      const btn = document.createElement('button');
      btn.textContent = lang.toUpperCase();
      btn.style.cssText = `
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 20px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
        background: ${this.currentLang === lang ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : 'transparent'};
        color: ${this.currentLang === lang ? 'white' : '#666'};
      `;

      btn.addEventListener('click', () => {
        this.switchLanguage(lang);
      });

      switcher.appendChild(btn);
    });

    document.body.appendChild(switcher);
  }

  switchLanguage(lang) {
    this.currentLang = lang;
    localStorage.setItem('al-api-lang', lang);
    this.applyLanguage(lang);
    
    // Update navigation
    const nav = document.getElementById('al-api-nav');
    if (nav) {
      nav.remove();
      this.injectNavigation();
    }

    // Update language switcher buttons
    document.querySelectorAll('#lang-switcher button').forEach(btn => {
      const btnLang = btn.textContent.toLowerCase();
      if (btnLang === lang) {
        btn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        btn.style.color = 'white';
      } else {
        btn.style.background = 'transparent';
        btn.style.color = '#666';
      }
    });
  }

  applyLanguage(lang) {
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      const ruText = el.getAttribute('data-i18n-ru');
      const enText = el.getAttribute('data-i18n-en');
      
      if (lang === 'en' && enText) {
        el.textContent = enText;
      } else if (lang === 'ru' && ruText) {
        el.textContent = ruText;
      }
    });

    // Update document language attribute
    document.documentElement.lang = lang;
  }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    const langSwitcher = new LanguageSwitcher();
    langSwitcher.init();
  });
} else {
  const langSwitcher = new LanguageSwitcher();
  langSwitcher.init();
}
