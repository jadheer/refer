import React, { Component } from 'react'
import { Text, View, TextInput, Button, Alert } from 'react-native'
import styles from './styles'

export default class Home extends Component {

    state = { username: "", password: "" }

    checkLogin() {
        const { username, password } = this.state
        // if(username == 'admin' && password == 'admin'){
        fetch('http://127.0.0.1:8000/api/auth/login/', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                "email": username,
                "password": password
            })
        })
        .then(data => {
            return data.json()
        })
        .then(data => {
            let userData = data
            if (userData.user) {
                this.props.navigation.navigate('dashboard')
                // console.warn("-------- " + userData.user.id)
            }
            else {
                Alert.alert('Error','Username/Password mismatch',[{
                    text:'Okay'
                }])
            }
        });
    }

    render() {
        const { heading, input, parent } = styles
        return (
            <View style={parent}>
                <Text style={heading}> Log into the app </Text>
                <TextInput style={input} placeholder="Username" onChangeText={text => this.setState({ username: text })} />
                <TextInput style={input} placeholder="Password" secureTextEntry={true} onChangeText={password => this.setState({ password: password })} />
                <Button title={"SignIn"} onPress={_ => this.checkLogin()} />
            </View>
        )
    }
}
