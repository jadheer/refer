import * as React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import Home from './app/components/Home';
import Dashboard from './app/components/Dashboard';
import { createDrawerNavigator } from '@react-navigation/drawer';

const Stack = createStackNavigator();

function MyStack() {
  return (
    <Stack.Navigator>
      <Stack.Screen name="home" component={Home} options={{ headerShown: false }}/>
      <Stack.Screen name="dashboard" component={Dashboard} />
    </Stack.Navigator>
  );
}

const Drawer = createDrawerNavigator();

export default function App() {
  return (
    // <NavigationContainer>
    //   <MyStack />
    // </NavigationContainer>

  <NavigationContainer>
    <Drawer.Navigator>
      <Drawer.Screen name="Home" component={MyStack} />
      <Drawer.Screen name="Dashboard" component={Dashboard} />
    </Drawer.Navigator>
  </NavigationContainer>

  );
}
