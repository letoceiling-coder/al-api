import { Routes, Route } from 'react-router-dom'
import Home from '../pages/Home'
import Documentation from '../pages/Documentation'
import StreamingGuide from '../pages/StreamingGuide'
import MultipartGuide from '../pages/MultipartGuide'
import ParametersGuide from '../pages/ParametersGuide'
import ErrorsGuide from '../pages/ErrorsGuide'
import Swagger from '../pages/Swagger'

const AppRoutes = () => {
  return (
    <Routes>
      <Route path="/" element={<Home />} />
      <Route path="/guide" element={<Documentation />} />
      <Route path="/streaming" element={<StreamingGuide />} />
      <Route path="/multipart" element={<MultipartGuide />} />
      <Route path="/parameters" element={<ParametersGuide />} />
      <Route path="/errors" element={<ErrorsGuide />} />
      <Route path="/swagger" element={<Swagger />} />
    </Routes>
  )
}

export default AppRoutes
