import { Routes, Route } from 'react-router-dom'
import ObjectsList from './pages/ObjectsList'
import ObjectDetail from './pages/ObjectDetail'
import './App.css'

function App() {
  return (
    <div className="app">
      <Routes>
        <Route path="/" element={<ObjectsList />} />
        <Route path="/:objectType/:id" element={<ObjectDetail />} />
      </Routes>
    </div>
  )
}

export default App
