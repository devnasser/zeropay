// Advanced Loading States Component
export const LoadingStates = {
    // Skeleton loader for product cards
    productSkeleton: () => `
        <div class="animate-pulse">
            <div class="bg-gray-300 h-48 w-full rounded-lg mb-4"></div>
            <div class="h-4 bg-gray-300 rounded w-3/4 mb-2"></div>
            <div class="h-4 bg-gray-300 rounded w-1/2 mb-2"></div>
            <div class="h-6 bg-gray-300 rounded w-1/3"></div>
        </div>
    `,
    
    // Shimmer effect for text
    shimmerText: (lines = 3) => {
        return Array(lines).fill().map((_, i) => `
            <div class="animate-pulse h-4 bg-gray-300 rounded mb-2" 
                 style="width: ${85 - (i * 15)}%"></div>
        `).join('');
    },
    
    // Progress bar with percentage
    progressBar: (percent = 0) => `
        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
            <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" 
                 style="width: ${percent}%"></div>
        </div>
        <p class="text-sm text-gray-600 text-center">${percent}% مكتمل</p>
    `,
    
    // Spinner variations
    spinner: (size = 'md', color = 'blue') => {
        const sizes = { sm: 'h-4 w-4', md: 'h-8 w-8', lg: 'h-12 w-12' };
        const colors = { 
            blue: 'text-blue-600', 
            green: 'text-green-600',
            red: 'text-red-600' 
        };
        
        return `
            <div class="flex justify-center items-center">
                <svg class="animate-spin ${sizes[size]} ${colors[color]}" 
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" 
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" 
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
        `;
    },
    
    // Loading overlay
    overlay: (message = 'جاري التحميل...') => `
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg p-8 max-w-sm w-full">
                ${LoadingStates.spinner('lg')}
                <p class="text-center mt-4 text-gray-700">${message}</p>
            </div>
        </div>
    `,
    
    // Success animation
    success: (message = 'تم بنجاح!') => `
        <div class="flex flex-col items-center animate-fade-in">
            <div class="rounded-full bg-green-100 p-3 mb-4">
                <svg class="h-12 w-12 text-green-600 animate-scale-in" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <p class="text-lg font-semibold text-gray-800">${message}</p>
        </div>
    `,
    
    // Error state
    error: (message = 'حدث خطأ!') => `
        <div class="flex flex-col items-center animate-fade-in">
            <div class="rounded-full bg-red-100 p-3 mb-4">
                <svg class="h-12 w-12 text-red-600" fill="none" 
                     stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <p class="text-lg font-semibold text-gray-800">${message}</p>
        </div>
    `
};

// Auto-inject CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes scale-in {
        from { transform: scale(0); }
        to { transform: scale(1); }
    }
    
    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }
    
    .animate-scale-in {
        animation: scale-in 0.3s ease-out;
    }
`;
document.head.appendChild(style);

export default LoadingStates;