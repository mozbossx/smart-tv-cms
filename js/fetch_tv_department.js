// Function to fetch TV department information
async function fetchTvDepartment(tvId) {
    try {
        const response = await fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        return data.tv_department;
    } catch (error) {
        console.error('Error fetching TV department:', error);
        return null;
    }
}

// Cache object to store department information
const departmentCache = new Map();

// Function to get TV department with caching
async function getTvDepartment(tvId) {
    // Check if department is in cache
    if (departmentCache.has(tvId)) {
        return departmentCache.get(tvId);
    }

    // If not in cache, fetch it
    const department = await fetchTvDepartment(tvId);
    if (department) {
        // Store in cache
        departmentCache.set(tvId, department);
    }
    return department;
}

// Function to clear the department cache
function clearDepartmentCache() {
    departmentCache.clear();
}

// Export the functions
window.TvDepartment = {
    fetch: fetchTvDepartment,
    get: getTvDepartment,
    clearCache: clearDepartmentCache
}; 