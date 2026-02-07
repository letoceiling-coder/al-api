import axios from 'axios'

const API_BASE_URL = '/api/trendagent'
const TRENDAGENT_TOKEN = '8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF'

// Создаем экземпляр axios с базовой конфигурацией
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Authorization': `Bearer ${TRENDAGENT_TOKEN}`,
    'Content-Type': 'application/json',
  },
  timeout: 120000, // 2 минуты для больших запросов
})

// Интерцептор для обработки ошибок
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response) {
      // Сервер вернул ошибку
      console.error('API Error:', error.response.data)
      return Promise.reject(error.response.data)
    } else if (error.request) {
      // Запрос был отправлен, но ответа не получено
      console.error('Network Error:', error.request)
      return Promise.reject({ message: 'Network error. Please check your connection.' })
    } else {
      // Что-то пошло не так при настройке запроса
      console.error('Error:', error.message)
      return Promise.reject({ message: error.message })
    }
  }
)

export const trendAgentAPI = {
  // Авторизация
  authenticate: async (phone, password) => {
    const response = await apiClient.post('/authenticate', {
      phone,
      password,
    })
    return response.data
  },

  // Получение списка городов
  getCities: async () => {
    const response = await apiClient.get('/cities')
    return response.data
  },

  // Квартиры
  getApartments: async (params) => {
    const response = await apiClient.post('/apartments', params)
    return response.data
  },

  getApartmentDetail: async (id, params) => {
    const response = await apiClient.post(`/apartments/${id}`, params)
    return response.data
  },

  // Шахматка для квартир
  getApartmentsCheckerboardBuildings: async (id, params) => {
    const response = await apiClient.post(`/apartments/${id}/checkerboard/buildings`, params)
    return response.data
  },

  getApartmentsCheckerboardApartments: async (id, params) => {
    const response = await apiClient.post(`/apartments/${id}/checkerboard/apartments`, params)
    return response.data
  },

  // Детальная информация о квартире
  getApartmentFlatDetail: async (blockId, apartmentId, params) => {
    const response = await apiClient.post(`/apartments/${blockId}/flat/${apartmentId}`, params)
    return response.data
  },

  // Паркинги
  getParkings: async (params) => {
    const response = await apiClient.post('/parkings', params)
    return response.data
  },

  getParkingDetail: async (id, params) => {
    const response = await apiClient.post(`/parkings/${id}`, params)
    return response.data
  },

  getParkingPlaces: async (id, params) => {
    const response = await apiClient.post(`/parkings/${id}/places`, params)
    return response.data
  },

  // Дома
  getHouses: async (params) => {
    const response = await apiClient.post('/houses', params)
    return response.data
  },

  getHouseDetail: async (id, params) => {
    const response = await apiClient.post(`/houses/${id}`, params)
    return response.data
  },

  // Шахматка для домов
  getCheckerboardBuildings: async (id, params) => {
    const response = await apiClient.post(`/houses/${id}/checkerboard/buildings`, params)
    return response.data
  },

  getCheckerboardApartments: async (id, params) => {
    const response = await apiClient.post(`/houses/${id}/checkerboard/apartments`, params)
    return response.data
  },

  // Участки
  getPlots: async (params) => {
    const response = await apiClient.post('/plots', params)
    return response.data
  },

  getPlotDetail: async (villageId, plotId, params) => {
    // Если plotId не передан, используем старый формат (village detail)
    if (!plotId && typeof villageId === 'string') {
      const response = await apiClient.post(`/plots/${villageId}`, params)
      return response.data
    }
    // Новый формат: детальная информация об участке
    const response = await apiClient.post(`/plots/${villageId}/plot/${plotId}`, params)
    return response.data
  },

  // Коммерческая недвижимость
  getCommercial: async (params) => {
    const response = await apiClient.post('/commercial', params)
    return response.data
  },

  getCommercialDetail: async (id, params) => {
    const response = await apiClient.post(`/commercial/${id}`, params)
    return response.data
  },

  // Универсальный список объектов
  getObjectsList: async (objectType, params) => {
    const response = await apiClient.post('/objects/list', {
      object_type: objectType,
      ...params,
    })
    return response.data
  },

  // Проекты домов / Подрядчики
  getHouseProjects: async (params) => {
    const response = await apiClient.post('/houseprojects', {
      object_type: 'contractors',
      ...params,
    })
    return response.data
  },

  getHouseProjectDetail: async (id, params) => {
    const response = await apiClient.post(`/houseprojects/${id}`, {
      object_type: 'contractors',
      ...params,
    })
    return response.data
  },
}

export default apiClient
