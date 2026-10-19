export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'penghuni'
  room_id: number | null
  room?: Room
  created_at?: string
}

export interface Room {
  id: number
  room_number: string
  type: string
  price: number
  capacity: number
  facilities: string
  description?: string | null
  status: 'available' | 'full' | 'maintenance'
  occupants_count?: number
  occupants?: User[]
  created_at?: string
  updated_at?: string
}

export interface Booking {
  id: number
  user_id: number
  room_id: number
  check_in_date: string | null
  check_out_date: string | null
  status: 'active' | 'checked_out' | 'cancelled'
  notes?: string | null
  room?: Room
  user?: User
  created_at?: string
}

export interface AuthResponse {
  message: string
  access_token: string
  token_type: string
  user: User
}

export interface ApiResponse<T = any> {
  message?: string
  data?: T
  status?: string
}
