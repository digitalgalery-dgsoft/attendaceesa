<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi Aplikasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-50 via-white to-cyan-50 py-10 relative overflow-hidden">
    <!-- Background Decor -->
    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
    <div class="absolute top-[20%] right-[-10%] w-96 h-96 bg-cyan-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
    <div class="absolute bottom-[-20%] left-[20%] w-96 h-96 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>

    <div class="w-full max-w-2xl relative z-10" x-data="{ step: 1, maxStep: 3, isSubmitting: false }">
        <div class="glass-panel rounded-2xl shadow-xl overflow-hidden">
            
            <!-- Header & Stepper -->
            <div class="bg-indigo-600 px-8 py-6 text-white">
                <h1 class="text-3xl font-bold tracking-tight">Instalasi Aplikasi</h1>
                <p class="text-indigo-100 mt-2 text-sm font-medium">Selesaikan 3 langkah mudah untuk memulai.</p>
                
                <!-- Progress Indicators -->
                <div class="flex items-center justify-between mt-8 relative">
                    <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-indigo-800 rounded-full"></div>
                    <div class="absolute left-0 top-1/2 transform -translate-y-1/2 h-1 bg-white rounded-full transition-all duration-500" :style="'width: ' + ((step - 1) / (maxStep - 1)) * 100 + '%'"></div>
                    
                    <template x-for="i in maxStep" :key="i">
                        <div class="relative flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors duration-300 border-2"
                                :class="step >= i ? 'bg-white text-indigo-600 border-white shadow-md' : 'bg-indigo-800 text-indigo-300 border-indigo-800'">
                                <span x-text="i"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Form Content -->
            <div class="p-8">
                @if(session('error'))
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 shadow-sm flex items-start" role="alert">
                        <svg class="w-5 h-5 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="block sm:inline font-medium">{{ session('error') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 shadow-sm">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="font-bold">Terdapat Kesalahan:</span>
                        </div>
                        <ul class="list-disc list-inside text-sm ml-2 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="/install" method="POST" @submit="isSubmitting = true">
                    @csrf

                    <!-- Step 1: App Settings -->
                    <div x-show="step === 1" x-transition.opacity.duration.300ms>
                        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                            </span>
                            Pengaturan URL Aplikasi
                        </h2>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">App URL</label>
                                <input type="url" name="app_url" value="{{ old('app_url', url('/')) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-3 bg-gray-50 transition-colors focus:bg-white" required placeholder="https://domain.com">
                                <p class="text-xs text-gray-500 mt-2">Pastikan ini adalah URL utama yang digunakan untuk mengakses aplikasi.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Database -->
                    <div x-show="step === 2" x-transition.opacity.duration.300ms style="display: none;">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                            </span>
                            Koneksi Database
                        </h2>
                        <div class="space-y-5">
                            <div x-data="{ dbType: '{{ old('db_connection', 'pgsql') }}' }">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipe Database (Driver)</label>
                                <select name="db_connection" x-model="dbType" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-3 bg-gray-50 focus:bg-white" required>
                                    <option value="pgsql">PostgreSQL (Disarankan)</option>
                                    <option value="mysql">MySQL / MariaDB</option>
                                </select>
                                <div class="grid grid-cols-2 gap-5 mt-5">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">DB Host</label>
                                        <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-3 bg-gray-50 focus:bg-white" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">DB Port</label>
                                        <input type="number" name="db_port" :value="dbType === 'pgsql' ? 5432 : 3306" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-3 bg-gray-50 focus:bg-white" required>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">DB Name (Nama Database)</label>
                                <input type="text" name="db_name" value="{{ old('db_name') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-3 bg-gray-50 focus:bg-white" required placeholder="attendance_db">
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">DB User</label>
                                    <input type="text" name="db_user" :value="dbType === 'pgsql' ? 'db_esa_akp' : 'root'" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-3 bg-gray-50 focus:bg-white" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">DB Password</label>
                                    <input type="password" name="db_password" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-3 bg-gray-50 focus:bg-white">
                                    <p class="text-xs text-gray-500 mt-1">Biarkan kosong jika tidak ada password.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Admin -->
                    <div x-show="step === 3" x-transition.opacity.duration.300ms style="display: none;">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </span>
                            Akun Super Admin
                        </h2>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Email Admin</label>
                                <input type="email" name="admin_email" value="{{ old('admin_email', 'admin@admin.com') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-3 bg-gray-50 focus:bg-white" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Password Admin</label>
                                <input type="password" name="admin_password" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-3 bg-gray-50 focus:bg-white" required minlength="6" placeholder="Minimal 6 karakter">
                            </div>
                        </div>
                        
                        <div class="mt-8 bg-indigo-50 rounded-lg p-4 border border-indigo-100 flex items-start">
                            <svg class="w-5 h-5 text-indigo-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm text-indigo-800 font-medium leading-relaxed">
                                Instalasi akan memperbarui file konfigurasi (.env) dan menyiapkan struktur database yang bersih. Akun ini akan diberi akses penuh (Super Admin).
                            </p>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="mt-8 pt-6 border-t border-gray-200 flex items-center" :class="step === 1 ? 'justify-end' : 'justify-between'">
                        
                        <button type="button" x-show="step > 1" @click="step--" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <svg class="mr-2 -ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            Kembali
                        </button>
                        
                        <button type="button" x-show="step < maxStep" @click="step++" class="inline-flex items-center px-6 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Lanjut
                            <svg class="ml-2 -mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>

                        <button type="submit" x-show="step === maxStep" :disabled="isSubmitting" class="inline-flex items-center px-6 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all disabled:opacity-75 disabled:cursor-not-allowed">
                            <span x-show="!isSubmitting">Mulai Instalasi</span>
                            <span x-show="isSubmitting" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Memproses...
                            </span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</body>
</html>
