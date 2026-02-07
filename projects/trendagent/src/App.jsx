import { Routes, Route } from 'react-router-dom'
import ObjectsList from './pages/ObjectsList'
import ObjectsTable from './pages/ObjectsTable'
import ObjectsPlans from './pages/ObjectsPlans'
import ObjectsMap from './pages/ObjectsMap'
import VillagesList from './pages/VillagesList'
import VillagesPlots from './pages/VillagesPlots'
import VillagesMap from './pages/VillagesMap'
import PlotDetail from './pages/PlotDetail'
import HouseProjectsList from './pages/HouseProjectsList'
import HouseProjectDetail from './pages/HouseProjectDetail'
import ObjectDetail from './pages/ObjectDetail'
import HousesCheckerboard from './pages/HousesCheckerboard'
import ApartmentsCheckerboard from './pages/ApartmentsCheckerboard'
import FlatDetail from './pages/FlatDetail'
import Parser from './pages/Parser'
import './App.css'

function App() {
  return (
    <div className="app">
      <Routes>
        <Route path="/" element={<ObjectsList />} />
        <Route path="/parser" element={<Parser />} />
        <Route path="/objects/table" element={<ObjectsTable />} />
        <Route path="/objects/plans" element={<ObjectsPlans />} />
        <Route path="/objects/map" element={<ObjectsMap />} />
        <Route path="/villages/list" element={<VillagesList />} />
        <Route path="/villages/plots" element={<VillagesPlots />} />
        <Route path="/villages/map" element={<VillagesMap />} />
        <Route path="/village/:slug/plot/:plotId" element={<PlotDetail />} />
        <Route path="/houseprojects" element={<HouseProjectsList />} />
        <Route path="/houseproject/:slug" element={<HouseProjectDetail />} />
        <Route path="/:objectType/:id" element={<ObjectDetail />} />
        <Route path="/houses/:id/checkerboard" element={<HousesCheckerboard />} />
        <Route path="/apartments/:id/checkerboard" element={<ApartmentsCheckerboard />} />
        <Route path="/apartments/:id/flat/:apartmentId" element={<FlatDetail />} />
      </Routes>
    </div>
  )
}

export default App
