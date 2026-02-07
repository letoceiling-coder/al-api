import './FlatHighlights.css'

const FlatHighlights = ({ discountsData, mortgageData, installmentsData }) => {
  const hasDiscounts = discountsData?.data && (Array.isArray(discountsData.data) ? discountsData.data.length > 0 : true)
  const hasMortgage = mortgageData?.data && (Array.isArray(mortgageData.data) ? mortgageData.data.length > 0 : true)
  const hasInstallments = installmentsData?.data && (Array.isArray(installmentsData.data) ? installmentsData.data.length > 0 : true)

  return (
    <div className="highlights-nav__container">
      <div className="highlights-nav__cards">
        <div className="highlights-nav__card">
          <div className="highlights-nav__content">
            <div className="highlights-nav__content-icon highlights-nav__content-icon_faded">
              <svg className="svg-icon trend-ui-icon-root trend-ui-icon-root__FireThin trend-ui-icon-root__FireThin-20" height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">
                <path xmlns="http://www.w3.org/2000/svg" fillRule="evenodd" clipRule="evenodd" d="M11.0064 1.20606C10.922 1.15772 10.7796 1.08221 10.7267 1.0542L10.722 1.05171C10.5671 0.975356 10.3826 0.983884 10.2359 1.07514C10.0892 1.16641 10 1.32693 10 1.49969C10 1.78125 10.0139 2.13779 10.0298 2.54407C10.0695 3.56213 10.1214 4.89247 9.99712 6.13752C9.91078 7.00232 9.74331 7.75125 9.46333 8.27004C9.19617 8.76511 8.86206 8.99969 8.4 8.99969C8.01558 8.99969 7.78258 8.91037 7.62734 8.78917C7.4684 8.66509 7.33374 8.46291 7.23103 8.13252C7.01583 7.44024 7 6.40379 7 4.99969C7 4.80647 6.88867 4.63055 6.71405 4.54783C6.53944 4.46511 6.33208 4.49098 6.18257 4.61338L6.1794 4.61611C6.15137 4.64022 6.07773 4.70356 6.035 4.74236C5.94611 4.82306 5.82147 4.94076 5.67303 5.0932C5.37655 5.39767 4.98281 5.84313 4.58898 6.41167C3.8037 7.54533 3 9.19522 3 11.2056C3 15.2105 6.11328 18.4997 10 18.4997C13.8867 18.4997 17 15.2105 17 11.2056C17 7.66851 15.4243 5.12493 13.8629 3.47744C13.0831 2.65459 12.3039 2.0516 11.7187 1.65358C11.4257 1.45434 11.1804 1.30575 11.0064 1.20606ZM6.00934 6.20686C5.82138 6.42567 5.61618 6.68492 5.41102 6.9811C4.6963 8.01289 4 9.46594 4 11.2056C4 14.7006 6.70702 17.4997 10 17.4997C13.293 17.4997 16 14.7006 16 11.2056C16 7.99264 14.5757 5.68329 13.1371 4.16533C12.4169 3.40547 11.6961 2.8476 11.1563 2.48045C11.1115 2.45001 11.0681 2.4209 11.026 2.39311L11.0267 2.41159C11.0666 3.43902 11.124 4.91638 10.9922 6.23686C10.9017 7.1429 10.7183 8.05022 10.3434 8.74497C9.95562 9.46345 9.3308 9.99969 8.4 9.99969C7.85585 9.99969 7.38707 9.87026 7.01195 9.5774C6.64053 9.28742 6.41626 8.88022 6.27611 8.42937C6.08945 7.82889 6.02892 7.06495 6.00934 6.20686Z" fill="#4C4C4C"></path>
              </svg>
            </div>
            <div className="highlights-nav__content-info">
              <div className="highlights-nav__content-title">
                <h6>Акции и скидки</h6>
              </div>
              <p>{hasDiscounts ? 'Есть акции и скидки' : 'У этой квартиры нет акций'}</p>
            </div>
          </div>
        </div>
        
        <div className="highlights-nav__card">
          <div className="highlights-nav__content">
            <div className="highlights-nav__content-icon highlights-nav__content-icon_faded">
              <svg className="svg-icon trend-ui-icon-root trend-ui-icon-root__CalculatorThin trend-ui-icon-root__CalculatorThin-20" height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">
                <path xmlns="http://www.w3.org/2000/svg" d="M13.8057 2.00488C15.9192 2.11192 17.5996 3.85983 17.5996 6V13.5996L17.5947 13.8057C17.4911 15.8511 15.8511 17.4911 13.8057 17.5947L13.5996 17.5996H6L5.79395 17.5947C3.74868 17.4909 2.10846 15.851 2.00488 13.8057L2 13.5996V6C2 3.85996 3.68056 2.11211 5.79395 2.00488L6 2H13.5996L13.8057 2.00488ZM6 3C4.34315 3 3 4.34315 3 6V13.5996C3 15.2565 4.34315 16.5996 6 16.5996H13.5996C15.2565 16.5996 16.5996 15.2565 16.5996 13.5996V6C16.5996 4.34315 15.2565 3 13.5996 3H6ZM6.70117 12.5098C6.92895 12.5564 7.10059 12.7584 7.10059 13C7.10059 13.2416 6.92895 13.4436 6.70117 13.4902L6.60059 13.5C6.32444 13.5 6.10059 13.2761 6.10059 13C6.10059 12.7239 6.32444 12.5 6.60059 12.5L6.70117 12.5098ZM9.90039 12.5098C10.1284 12.5563 10.2998 12.7583 10.2998 13C10.2998 13.2417 10.1284 13.4437 9.90039 13.4902L9.7998 13.5C9.52366 13.5 9.2998 13.2761 9.2998 13C9.2998 12.7239 9.52366 12.5 9.7998 12.5L9.90039 12.5098ZM13.1006 12.5098C13.3286 12.5563 13.5 12.7583 13.5 13C13.5 13.2417 13.3286 13.4437 13.1006 13.4902L13 13.5C12.7239 13.5 12.5 13.2761 12.5 13C12.5 12.7239 12.7239 12.5 13 12.5L13.1006 12.5098ZM6.70117 9.30957C6.9289 9.35621 7.10047 9.55831 7.10059 9.7998C7.10059 10.0414 6.92893 10.2434 6.70117 10.29L6.60059 10.2998C6.32444 10.2998 6.10059 10.0759 6.10059 9.7998C6.10072 9.52377 6.32452 9.2998 6.60059 9.2998L6.70117 9.30957ZM9.90039 9.30957C10.1283 9.35604 10.2997 9.55817 10.2998 9.7998C10.2998 10.0415 10.1284 10.2435 9.90039 10.29L9.7998 10.2998C9.52366 10.2998 9.2998 10.0759 9.2998 9.7998C9.29994 9.52377 9.52374 9.2998 9.7998 9.2998L9.90039 9.30957ZM13.1006 9.30957C13.3285 9.35604 13.4999 9.55817 13.5 9.7998C13.5 10.0415 13.3286 10.2435 13.1006 10.29L13 10.2998C12.7239 10.2998 12.5 10.0759 12.5 9.7998C12.5001 9.52377 12.7239 9.2998 13 9.2998L13.1006 9.30957ZM13.9512 6.10938C14.1788 6.15603 14.3504 6.3582 14.3506 6.59961C14.3506 6.84113 14.1789 7.04311 13.9512 7.08984L13.8506 7.09961H5.85059C5.57444 7.09961 5.35059 6.87575 5.35059 6.59961C5.35078 6.32364 5.57457 6.09961 5.85059 6.09961H13.8506L13.9512 6.10938Z" fill="#52525B"></path>
              </svg>
            </div>
            <div className="highlights-nav__content-info">
              <div className="highlights-nav__content-title">
                <h6>Ипотека</h6>
              </div>
              <p>{hasMortgage ? 'Есть ипотечные программы' : 'В квартире нет ипотеки'}</p>
            </div>
          </div>
        </div>
        
        <div className="highlights-nav__card">
          <div className="highlights-nav__content">
            <div className="highlights-nav__content-icon highlights-nav__content-icon_faded">
              <svg className="svg-icon trend-ui-icon-root trend-ui-icon-root__ClockThin trend-ui-icon-root__ClockThin-20" height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">
                <path xmlns="http://www.w3.org/2000/svg" fillRule="evenodd" clipRule="evenodd" d="M10 2.25C5.71979 2.25 2.25 5.71979 2.25 10C2.25 14.2802 5.71979 17.75 10 17.75C14.2802 17.75 17.75 14.2802 17.75 10C17.75 5.71979 14.2802 2.25 10 2.25ZM1.25 10C1.25 5.16751 5.16751 1.25 10 1.25C14.8325 1.25 18.75 5.16751 18.75 10C18.75 14.8325 14.8325 18.75 10 18.75C5.16751 18.75 1.25 14.8325 1.25 10ZM10 4.5C10.2761 4.5 10.5 4.72386 10.5 5V9.69098L13.5569 11.2195C13.8039 11.3429 13.904 11.6433 13.7805 11.8903C13.6571 12.1373 13.3567 12.2374 13.1097 12.1139L9.77639 10.4472C9.607 10.3625 9.5 10.1894 9.5 10V5C9.5 4.72386 9.72386 4.5 10 4.5Z" fill="#4C4C4C"></path>
              </svg>
            </div>
            <div className="highlights-nav__content-info">
              <div className="highlights-nav__content-title">
                <h6>Рассрочка</h6>
              </div>
              <p>{hasInstallments ? 'Есть рассрочка' : 'В квартире нет рассрочки'}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default FlatHighlights
