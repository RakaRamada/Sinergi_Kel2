<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Buat akun dulu ya!</title>
    <link href="/Sinergi/public/css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">

    <main class="w-full max-w-4xl mx-auto rounded-lg shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">
        
        <div class="hidden md:block">
            <img src="/public/img/background.jpg" 
                 alt="Diskusi tim" 
                 class="w-full h-full object-cover">
        </div>

        <div class="bg-white p-10 md:p-12">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6 text-center">
                Buat akun dulu ya!
            </h1>

            <form action="#" method="POST" class="space-y-5">
                
                <div>
                    <label for="role" class="sr-only">Peran</label>
                    <select name="role" id="role"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 text-gray-500 focus:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                        <option value="umum">Umum</option>
                    </select>
                </div>

                <div>
                    <label for="username" class="sr-only">Username</label>
                    <input type="text" name="username" id="username" 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           placeholder="Username" required>
                </div>

                <div>
                    <label for="email" class="sr-only">Email</label>
                    <input type="email" name="email" id="email" 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           placeholder="Email" required>
                </div>
                
                <div class="relative">
                    <label for="password" class="sr-only">Password</label>
                    <input type="password" name="password" id="password" 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           placeholder="Password" required>
                    <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 10.224 7.29 6.332 12 6.332c4.71 0 8.577 3.892 9.964 5.351a1.012 1.012 0 0 1 0 .639C20.577 13.776 16.71 17.668 12 17.668c-4.71 0-8.577-3.892-9.964-5.351Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                </div>

                <button type="submit" 
                        class="w-full py-3 px-4 bg-gray-800 text-white font-bold rounded-lg hover:bg-gray-700 transition-colors">
                    Sign Up
                </button>
            </form>

            <p class="text-sm text-center text-gray-600 mt-6">
                Sudah punya akun? 
                <a href="/login" class="text-blue-600 font-medium hover:underline">Sign in.</a>
            </p>
        </div>

    </main>

</body>
</html>