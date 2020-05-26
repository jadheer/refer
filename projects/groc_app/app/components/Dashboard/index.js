import React, { Component } from 'react'
import { Text, View } from 'react-native'

export default class Dashboard extends Component {
    render() {
        return (
            <View style={{
                padding: 30,
                width:'80%',
                flexDirection:'row',
                justifyContent:'space-around',
                alignItems:'stretch'
            }}>
                <View style={{
                    flex: 1,
                    backgroundColor:'red'
                }}>
                    <Text> 1 </Text>
                </View>

                <View style={{
                    flex: 2,
                    backgroundColor:'green'
                }}>
                    <Text> 2 </Text>
                </View>

                <View style={{
                    // flex: 1,
                    backgroundColor:'blue'
                }}>
                    <Text> 3 </Text>
                </View>

            </View>
        )
    }
}
