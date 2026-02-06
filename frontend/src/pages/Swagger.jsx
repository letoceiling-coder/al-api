import { useEffect } from 'react'
import './Page.css'

const Swagger = () => {
  useEffect(() => {
    // Redirect to Swagger UI
    window.location.href = 'https://api.siteaccess.ru/api/documentation'
  }, [])

  return (
    <div className="page">
      <div className="page-container">
        <h1>📘 Swagger UI</h1>
        <p>Перенаправление на Swagger UI...</p>
      </div>
    </div>
  )
}

export default Swagger
