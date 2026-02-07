import { Link, useLocation } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import './Navigation.css'

const Navigation = () => {
  const { t } = useTranslation()
  const location = useLocation()

  const navItems = [
    { path: '/', icon: '🏠', key: 'home' },
    { path: '/guide', icon: '📖', key: 'documentation' },
    { path: '/streaming', icon: '📡', key: 'streaming' },
    { path: '/multipart', icon: '📎', key: 'fileUpload' },
    { path: '/parameters', icon: '⚙️', key: 'parameters' },
    { path: '/errors', icon: '⚠️', key: 'errors' },
    { path: '/swagger', icon: '📘', key: 'swagger' },
  ]

  return (
    <nav className="navigation">
      <div className="nav-container">
        <Link to="/" className="nav-logo">
          <span className="logo-icon">🤖</span>
          <span className="logo-text">AL API Gateway</span>
        </Link>
        <div className="nav-links">
          {navItems.map((item) => (
            <Link
              key={item.path}
              to={item.path}
              className={`nav-link ${location.pathname === item.path ? 'active' : ''}`}
            >
              <span className="nav-icon">{item.icon}</span>
              <span className="nav-text">{t(`nav.${item.key}`)}</span>
            </Link>
          ))}
        </div>
      </div>
    </nav>
  )
}

export default Navigation
