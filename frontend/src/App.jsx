import { BrowserRouter } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import Navigation from './components/Navigation'
import AppRoutes from './routes/AppRoutes'
import LanguageSwitcher from './components/LanguageSwitcher'
import './App.css'

function App() {
  const { i18n } = useTranslation()

  return (
    <BrowserRouter>
      <div className="app">
        <Navigation />
        <LanguageSwitcher />
        <main className="main-content">
          <AppRoutes />
        </main>
      </div>
    </BrowserRouter>
  )
}

export default App
