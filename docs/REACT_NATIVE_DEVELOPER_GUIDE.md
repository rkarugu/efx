# React Native Developer Guide - Kaninichapchap Mobile App

## Quick Start for Developers

### Prerequisites
```bash
Node.js: 16.x or higher
npm: 8.x or higher
React Native CLI: Latest
Xcode: 14+ (for iOS)
Android Studio: Latest (for Android)
```

### Project Setup
```bash
# Initialize React Native project
npx react-native init KaninichapchapApp --template react-native-template-typescript

cd KaninichapchapApp

# Install dependencies
npm install @react-navigation/native @react-navigation/bottom-tabs @react-navigation/stack
npm install react-native-screens react-native-safe-area-context
npm install @reduxjs/toolkit react-redux
npm install axios
npm install @react-native-async-storage/async-storage
npm install react-native-sqlite-storage
npm install react-native-maps
npm install react-native-camera react-native-image-picker
npm install react-native-signature-canvas
npm install @react-native-firebase/app @react-native-firebase/messaging
npm install react-native-vector-icons
npm install react-native-biometrics
npm install react-native-geolocation-service

# iOS specific
cd ios && pod install && cd ..
```

---

## Project Structure

```
KaninichapchapApp/
├── src/
│   ├── api/
│   │   ├── client.ts              # Axios configuration
│   │   ├── auth.ts                # Authentication APIs
│   │   ├── customers.ts           # Customer APIs
│   │   ├── products.ts            # Product APIs
│   │   ├── orders.ts              # Order APIs
│   │   ├── payments.ts            # Payment APIs
│   │   └── deliveries.ts          # Delivery APIs
│   │
│   ├── components/
│   │   ├── common/
│   │   │   ├── Button.tsx
│   │   │   ├── Input.tsx
│   │   │   ├── Card.tsx
│   │   │   ├── Loading.tsx
│   │   │   └── EmptyState.tsx
│   │   ├── customer/
│   │   │   ├── CustomerCard.tsx
│   │   │   └── CustomerList.tsx
│   │   ├── product/
│   │   │   ├── ProductCard.tsx
│   │   │   └── ProductGrid.tsx
│   │   └── order/
│   │       ├── OrderCard.tsx
│   │       └── CartItem.tsx
│   │
│   ├── screens/
│   │   ├── auth/
│   │   │   ├── LoginScreen.tsx
│   │   │   ├── OTPScreen.tsx
│   │   │   └── BiometricSetupScreen.tsx
│   │   ├── dashboard/
│   │   │   └── DashboardScreen.tsx
│   │   ├── shift/
│   │   │   ├── OpenShiftScreen.tsx
│   │   │   └── CloseShiftScreen.tsx
│   │   ├── customer/
│   │   │   ├── CustomerListScreen.tsx
│   │   │   └── CustomerDetailScreen.tsx
│   │   ├── product/
│   │   │   ├── ProductListScreen.tsx
│   │   │   └── ProductDetailScreen.tsx
│   │   ├── order/
│   │   │   ├── CreateOrderScreen.tsx
│   │   │   ├── OrderHistoryScreen.tsx
│   │   │   └── OrderDetailScreen.tsx
│   │   ├── payment/
│   │   │   ├── CollectPaymentScreen.tsx
│   │   │   └── PaymentReceiptScreen.tsx
│   │   ├── delivery/
│   │   │   ├── DeliveryListScreen.tsx
│   │   │   ├── DeliveryDetailScreen.tsx
│   │   │   └── ConfirmDeliveryScreen.tsx
│   │   ├── expense/
│   │   │   └── RecordExpenseScreen.tsx
│   │   ├── report/
│   │   │   └── SalesReportScreen.tsx
│   │   └── profile/
│   │       └── ProfileScreen.tsx
│   │
│   ├── store/
│   │   ├── index.ts               # Redux store configuration
│   │   ├── slices/
│   │   │   ├── authSlice.ts
│   │   │   ├── customerSlice.ts
│   │   │   ├── productSlice.ts
│   │   │   ├── orderSlice.ts
│   │   │   ├── cartSlice.ts
│   │   │   └── shiftSlice.ts
│   │   └── middleware/
│   │       └── offlineSync.ts
│   │
│   ├── navigation/
│   │   ├── AppNavigator.tsx       # Main navigation
│   │   ├── AuthNavigator.tsx      # Auth flow
│   │   └── MainNavigator.tsx      # Bottom tabs
│   │
│   ├── utils/
│   │   ├── storage.ts             # AsyncStorage wrapper
│   │   ├── database.ts            # SQLite wrapper
│   │   ├── location.ts            # Location services
│   │   ├── camera.ts              # Camera utilities
│   │   ├── biometric.ts           # Biometric auth
│   │   └── helpers.ts             # Helper functions
│   │
│   ├── constants/
│   │   ├── colors.ts
│   │   ├── sizes.ts
│   │   └── api.ts
│   │
│   └── types/
│       ├── auth.ts
│       ├── customer.ts
│       ├── product.ts
│       ├── order.ts
│       └── navigation.ts
│
├── android/                       # Android native code
├── ios/                           # iOS native code
├── App.tsx                        # Root component
└── package.json
```

---

## Key Implementation Examples

### 1. API Client Setup

```typescript
// src/api/client.ts
import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_BASE_URL = 'https://kaninichapchap.efficentrix.co.ke/api';

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor - Add auth token
apiClient.interceptors.request.use(
  async (config) => {
    const token = await AsyncStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor - Handle errors
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Token expired, logout user
      await AsyncStorage.removeItem('auth_token');
      // Navigate to login
    }
    return Promise.reject(error);
  }
);

export default apiClient;
```

### 2. Authentication API

```typescript
// src/api/auth.ts
import apiClient from './client';

export const authAPI = {
  login: async (phoneNumber: string, password: string, deviceId: string) => {
    const response = await apiClient.post('/getLogin', {
      phone_number: phoneNumber,
      password,
      device_id: deviceId,
    });
    return response.data;
  },

  validatePhoneNumber: async (phoneNumber: string) => {
    const response = await apiClient.post('/auth/validate-user-phonenumber', {
      phone_number: phoneNumber,
    });
    return response.data;
  },

  validateOTP: async (phoneNumber: string, otp: string, otpId: string) => {
    const response = await apiClient.post('/auth/validate-otp', {
      phone_number: phoneNumber,
      otp,
      otp_id: otpId,
    });
    return response.data;
  },
};
```

### 3. Redux Store Setup

```typescript
// src/store/index.ts
import { configureStore } from '@reduxjs/toolkit';
import authReducer from './slices/authSlice';
import customerReducer from './slices/customerSlice';
import productReducer from './slices/productSlice';
import orderReducer from './slices/orderSlice';
import cartReducer from './slices/cartSlice';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    customer: customerReducer,
    product: productReducer,
    order: orderReducer,
    cart: cartReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: false,
    }),
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
```

### 4. Auth Slice

```typescript
// src/store/slices/authSlice.ts
import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import { authAPI } from '../../api/auth';
import AsyncStorage from '@react-native-async-storage/async-storage';

interface AuthState {
  user: any | null;
  token: string | null;
  isAuthenticated: boolean;
  loading: boolean;
  error: string | null;
}

const initialState: AuthState = {
  user: null,
  token: null,
  isAuthenticated: false,
  loading: false,
  error: null,
};

export const login = createAsyncThunk(
  'auth/login',
  async ({ phoneNumber, password, deviceId }: any) => {
    const response = await authAPI.login(phoneNumber, password, deviceId);
    await AsyncStorage.setItem('auth_token', response.token);
    await AsyncStorage.setItem('user', JSON.stringify(response.user));
    return response;
  }
);

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    logout: (state) => {
      state.user = null;
      state.token = null;
      state.isAuthenticated = false;
      AsyncStorage.removeItem('auth_token');
      AsyncStorage.removeItem('user');
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(login.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(login.fulfilled, (state, action) => {
        state.loading = false;
        state.user = action.payload.user;
        state.token = action.payload.token;
        state.isAuthenticated = true;
      })
      .addCase(login.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message || 'Login failed';
      });
  },
});

export const { logout } = authSlice.actions;
export default authSlice.reducer;
```

### 5. Login Screen

```typescript
// src/screens/auth/LoginScreen.tsx
import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { useDispatch, useSelector } from 'react-redux';
import { login } from '../../store/slices/authSlice';
import { AppDispatch, RootState } from '../../store';

const LoginScreen = ({ navigation }: any) => {
  const [phoneNumber, setPhoneNumber] = useState('');
  const [password, setPassword] = useState('');
  const dispatch = useDispatch<AppDispatch>();
  const { loading, error } = useSelector((state: RootState) => state.auth);

  const handleLogin = async () => {
    if (!phoneNumber || !password) {
      alert('Please enter phone number and password');
      return;
    }

    try {
      await dispatch(
        login({
          phoneNumber,
          password,
          deviceId: 'device-id-here',
        })
      ).unwrap();
      // Navigation handled by root navigator
    } catch (err) {
      alert('Login failed. Please try again.');
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Welcome Back!</Text>
      <Text style={styles.subtitle}>Sign in to continue</Text>

      <TextInput
        style={styles.input}
        placeholder="Phone Number"
        value={phoneNumber}
        onChangeText={setPhoneNumber}
        keyboardType="phone-pad"
      />

      <TextInput
        style={styles.input}
        placeholder="Password"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
      />

      {error && <Text style={styles.error}>{error}</Text>}

      <TouchableOpacity
        style={styles.button}
        onPress={handleLogin}
        disabled={loading}
      >
        {loading ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text style={styles.buttonText}>LOGIN</Text>
        )}
      </TouchableOpacity>

      <TouchableOpacity onPress={() => navigation.navigate('ForgotPassword')}>
        <Text style={styles.link}>Forgot Password?</Text>
      </TouchableOpacity>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    justifyContent: 'center',
    backgroundColor: '#fff',
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    marginBottom: 10,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 16,
    color: '#666',
    marginBottom: 30,
    textAlign: 'center',
  },
  input: {
    height: 50,
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    paddingHorizontal: 15,
    marginBottom: 15,
    fontSize: 16,
  },
  button: {
    height: 50,
    backgroundColor: '#007AFF',
    borderRadius: 8,
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 10,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  link: {
    color: '#007AFF',
    textAlign: 'center',
    marginTop: 15,
  },
  error: {
    color: '#FF3B30',
    marginBottom: 10,
    textAlign: 'center',
  },
});

export default LoginScreen;
```

### 6. Offline Storage

```typescript
// src/utils/database.ts
import SQLite from 'react-native-sqlite-storage';

SQLite.enablePromise(true);

class Database {
  private db: any;

  async init() {
    this.db = await SQLite.openDatabase({
      name: 'kaninichapchap.db',
      location: 'default',
    });

    await this.createTables();
  }

  async createTables() {
    await this.db.executeSql(`
      CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY,
        name TEXT,
        phone TEXT,
        location TEXT,
        balance REAL,
        data TEXT,
        synced INTEGER DEFAULT 0
      )
    `);

    await this.db.executeSql(`
      CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY,
        stock_id_code TEXT,
        title TEXT,
        price REAL,
        quantity_on_hand INTEGER,
        data TEXT,
        synced INTEGER DEFAULT 0
      )
    `);

    await this.db.executeSql(`
      CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_id INTEGER,
        total_amount REAL,
        payment_method TEXT,
        data TEXT,
        synced INTEGER DEFAULT 0,
        created_at TEXT
      )
    `);
  }

  async saveCustomers(customers: any[]) {
    for (const customer of customers) {
      await this.db.executeSql(
        `INSERT OR REPLACE INTO customers (id, name, phone, location, balance, data, synced)
         VALUES (?, ?, ?, ?, ?, ?, ?)`,
        [
          customer.id,
          customer.name,
          customer.phone,
          customer.location,
          customer.balance,
          JSON.stringify(customer),
          1,
        ]
      );
    }
  }

  async getCustomers() {
    const [results] = await this.db.executeSql('SELECT * FROM customers');
    const customers = [];
    for (let i = 0; i < results.rows.length; i++) {
      const row = results.rows.item(i);
      customers.push(JSON.parse(row.data));
    }
    return customers;
  }

  async saveOrder(order: any) {
    await this.db.executeSql(
      `INSERT INTO orders (customer_id, total_amount, payment_method, data, synced, created_at)
       VALUES (?, ?, ?, ?, ?, ?)`,
      [
        order.customer_id,
        order.total_amount,
        order.payment_method,
        JSON.stringify(order),
        0,
        new Date().toISOString(),
      ]
    );
  }

  async getUnsyncedOrders() {
    const [results] = await this.db.executeSql(
      'SELECT * FROM orders WHERE synced = 0'
    );
    const orders = [];
    for (let i = 0; i < results.rows.length; i++) {
      const row = results.rows.item(i);
      orders.push(JSON.parse(row.data));
    }
    return orders;
  }

  async markOrderSynced(id: number) {
    await this.db.executeSql('UPDATE orders SET synced = 1 WHERE id = ?', [id]);
  }
}

export default new Database();
```

---

## Testing

### Unit Tests
```bash
npm test
```

### E2E Tests (Detox)
```bash
# Install Detox
npm install --save-dev detox

# Run tests
detox test --configuration ios.sim.debug
```

---

## Build & Deploy

### Android
```bash
# Generate APK
cd android
./gradlew assembleRelease

# Generate AAB (for Play Store)
./gradlew bundleRelease
```

### iOS
```bash
# Open Xcode
open ios/KaninichapchapApp.xcworkspace

# Archive and upload to App Store Connect
```

---

## Performance Optimization

1. **Use React.memo** for expensive components
2. **Lazy load** screens with React.lazy
3. **Optimize images** with FastImage
4. **Use FlatList** for long lists
5. **Implement pagination** for API calls
6. **Cache API responses**
7. **Use Hermes** JavaScript engine

---

## Security Best Practices

1. **Never hardcode** API keys or secrets
2. **Use SSL pinning** for API calls
3. **Encrypt sensitive data** in AsyncStorage
4. **Implement certificate pinning**
5. **Use ProGuard** (Android) and obfuscation
6. **Validate all user inputs**
7. **Implement rate limiting**

---

## Resources

- **React Native Docs**: https://reactnative.dev/
- **React Navigation**: https://reactnavigation.org/
- **Redux Toolkit**: https://redux-toolkit.js.org/
- **API Documentation**: See API_DOCUMENTATION.md

---

## Support

For development support, contact: dev@efficentrix.com
